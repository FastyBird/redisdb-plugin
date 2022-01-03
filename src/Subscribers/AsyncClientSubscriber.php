<?php declare(strict_types = 1);

/**
 * AsyncClientSubscriber.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Subscribers
 * @since          0.2.0
 *
 * @date           09.10.21
 */

namespace FastyBird\RedisDbExchangePlugin\Subscribers;

use FastyBird\ExchangePlugin\Consumer as ExchangePluginConsumer;
use FastyBird\ModulesMetadata\Exceptions as ModulesMetadataExceptions;
use FastyBird\ModulesMetadata\Loaders as ModulesMetadataLoaders;
use FastyBird\ModulesMetadata\Schemas as ModulesMetadataSchemas;
use FastyBird\ModulesMetadata\Types as ModulesMetadataTypes;
use FastyBird\RedisDbExchangePlugin\Events;
use FastyBird\RedisDbExchangePlugin\Exceptions;
use Nette\Utils;
use Psr\EventDispatcher as PsrEventDispatcher;
use Psr\Log;
use Symfony\Component\EventDispatcher;
use Throwable;

/**
 * Redis async clients subscriber
 *
 * @package         FastyBird:RedisDbExchangePlugin!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class AsyncClientSubscriber implements EventDispatcher\EventSubscriberInterface
{

	/** @var ExchangePluginConsumer\IConsumer|null */
	private ?ExchangePluginConsumer\IConsumer $consumer;

	/** @var ModulesMetadataLoaders\ISchemaLoader */
	private ModulesMetadataLoaders\ISchemaLoader $schemaLoader;

	/** @var ModulesMetadataSchemas\IValidator */
	private ModulesMetadataSchemas\IValidator $validator;

	/** @var PsrEventDispatcher\EventDispatcherInterface */
	private PsrEventDispatcher\EventDispatcherInterface $dispatcher;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		ModulesMetadataLoaders\ISchemaLoader $schemaLoader,
		ModulesMetadataSchemas\IValidator $validator,
		PsrEventDispatcher\EventDispatcherInterface $dispatcher,
		?ExchangePluginConsumer\IConsumer $consumer = null,
		?Log\LoggerInterface $logger = null
	) {
		$this->schemaLoader = $schemaLoader;
		$this->validator = $validator;

		$this->dispatcher = $dispatcher;

		$this->consumer = $consumer;

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * {@inheritDoc}
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			Events\MessageReceivedEvent::class => 'handleMessage',
		];
	}

	public function handleMessage(Events\MessageReceivedEvent $event): void
	{
		$this->dispatcher->dispatch(new Events\BeforeMessageHandledEvent($event->getPayload()));

		try {
			$data = Utils\ArrayHash::from(Utils\Json::decode($event->getPayload(), Utils\Json::FORCE_ARRAY));

			if (
				$data->offsetExists('origin')
				&& $data->offsetExists('routing_key')
				&& $data->offsetExists('data')
			) {
				$this->handle(
					ModulesMetadataTypes\ModuleOriginType::get($data->offsetGet('origin')),
					ModulesMetadataTypes\RoutingKeyType::get($data->offsetGet('routing_key')),
					$data->offsetGet('data')
				);

			} else {
				// Log error action reason
				$this->logger->warning('[FB:PLUGIN:REDISDB_EXCHANGE] Received message is not in valid format');
			}
		} catch (Utils\JsonException $ex) {
			// Log error action reason
			$this->logger->warning('[FB:PLUGIN:REDISDB_EXCHANGE] Received message is not valid json', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

		} catch (Exceptions\TerminateException $ex) {
			$event->getClient()->close();
		}

		$this->dispatcher->dispatch(new Events\AfterMessageHandledEvent($event->getPayload()));
	}

	/**
	 * @param ModulesMetadataTypes\ModuleOriginType $origin
	 * @param ModulesMetadataTypes\RoutingKeyType $routingKey
	 * @param Utils\ArrayHash $data
	 *
	 * @throws Utils\JsonException
	 */
	private function handle(
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

		try {
			if ($this->consumer !== null) {
				$this->consumer->consume($origin, $routingKey, $data);
			}
		} catch (Exceptions\UnprocessableMessageException $ex) {
			// Log error consume reason
			$this->logger->error('[FB:PLUGIN:REDISDB_EXCHANGE] Message could not be handled', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			return;
		}
	}

}
