<?php declare(strict_types = 1);

/**
 * ConsumerProxy.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Consumer
 * @since          0.1.0
 *
 * @date           17.09.21
 */

namespace FastyBird\RedisDbExchangePlugin\Consumer;

use FastyBird\ExchangePlugin\Consumer as ExchangePluginConsumer;
use FastyBird\ExchangePlugin\Events as ExchangePluginEvents;
use FastyBird\ModulesMetadata\Exceptions as ModulesMetadataExceptions;
use FastyBird\ModulesMetadata\Loaders as ModulesMetadataLoaders;
use FastyBird\ModulesMetadata\Schemas as ModulesMetadataSchemas;
use FastyBird\ModulesMetadata\Types as ModulesMetadataTypes;
use FastyBird\RedisDbExchangePlugin\Exceptions;
use Nette;
use Nette\Utils;
use Psr\Log;
use SplObjectStorage;
use Symfony\Contracts\EventDispatcher;
use Throwable;

/**
 * Exchange message consumer proxy
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Consumer
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConsumerProxy implements IConsumer
{

	use Nette\SmartObject;

	/** @var SplObjectStorage<ExchangePluginConsumer\IConsumer, null> */
	private SplObjectStorage $consumers;

	/** @var ModulesMetadataLoaders\ISchemaLoader */
	private ModulesMetadataLoaders\ISchemaLoader $schemaLoader;

	/** @var ModulesMetadataSchemas\IValidator */
	private ModulesMetadataSchemas\IValidator $validator;

	/** @var EventDispatcher\EventDispatcherInterface */
	private EventDispatcher\EventDispatcherInterface $dispatcher;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		ModulesMetadataLoaders\ISchemaLoader $schemaLoader,
		ModulesMetadataSchemas\IValidator $validator,
		EventDispatcher\EventDispatcherInterface $dispatcher,
		?Log\LoggerInterface $logger = null
	) {
		$this->schemaLoader = $schemaLoader;
		$this->validator = $validator;

		$this->dispatcher = $dispatcher;

		$this->consumers = new SplObjectStorage();

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * {@inheritDoc}
	 */
	public function registerConsumer(ExchangePluginConsumer\IConsumer $consumer): void
	{
		if (!$this->consumers->contains($consumer)) {
			$this->consumers->attach($consumer);
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\TerminateException
	 */
	public function consume(
		ModulesMetadataTypes\ModuleOriginType $origin,
		ModulesMetadataTypes\RoutingKeyType $routingKey,
		Utils\ArrayHash $data
	): void {
		try {
			$schema = $this->schemaLoader->load($origin->getValue(), $routingKey->getValue());

		} catch (ModulesMetadataExceptions\InvalidArgumentException $ex) {
			return;
		}

		try {
			$data = $this->validator->validate(Utils\Json::encode($data), $schema);

		} catch (Throwable $ex) {
			return;
		}

		/** @var ExchangePluginConsumer\IConsumer $consumer */
		foreach ($this->consumers as $consumer) {
			try {
				$this->processMessage($origin, $routingKey, $data, $consumer);

			} catch (Exceptions\UnprocessableMessageException $ex) {
				// Log error consume reason
				$this->logger->error('[FB:PLUGIN:REDISDB_EXCHANGE] Message could not be consumed', [
					'exception' => [
						'message' => $ex->getMessage(),
						'code'    => $ex->getCode(),
					],
				]);

				return;
			}
		}

		$this->dispatcher->dispatch(new ExchangePluginEvents\MessageConsumedEvent($origin, $routingKey, $data));
	}

	/**
	 * @param ModulesMetadataTypes\ModuleOriginType $origin
	 * @param ModulesMetadataTypes\RoutingKeyType $routingKey
	 * @param Utils\ArrayHash $data
	 * @param ExchangePluginConsumer\IConsumer $consumer
	 *
	 * @return void
	 */
	private function processMessage(
		ModulesMetadataTypes\ModuleOriginType $origin,
		ModulesMetadataTypes\RoutingKeyType $routingKey,
		Utils\ArrayHash $data,
		ExchangePluginConsumer\IConsumer $consumer
	): void {
		try {
			$consumer->consume($origin, $routingKey, $data);

		} catch (Exceptions\TerminateException $ex) {
			throw $ex;

		} catch (Throwable $ex) {
			throw new Exceptions\UnprocessableMessageException('Received message could not be consumed', $ex->getCode(), $ex);
		}
	}

}
