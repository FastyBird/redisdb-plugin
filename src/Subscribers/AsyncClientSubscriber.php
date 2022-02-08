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

use FastyBird\Exchange\Consumer as ExchangeConsumer;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Metadata\Loaders as MetadataLoaders;
use FastyBird\Metadata\Schemas as MetadataSchemas;
use FastyBird\Metadata\Types as MetadataTypes;
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

	/** @var ExchangeConsumer\Consumer|null */
	private ?ExchangeConsumer\Consumer $consumer;

	/** @var MetadataLoaders\ISchemaLoader */
	private MetadataLoaders\ISchemaLoader $schemaLoader;

	/** @var MetadataSchemas\IValidator */
	private MetadataSchemas\IValidator $validator;

	/** @var PsrEventDispatcher\EventDispatcherInterface|null */
	private ?PsrEventDispatcher\EventDispatcherInterface $dispatcher;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		MetadataLoaders\ISchemaLoader $schemaLoader,
		MetadataSchemas\IValidator $validator,
		?PsrEventDispatcher\EventDispatcherInterface $dispatcher = null,
		?ExchangeConsumer\Consumer $consumer = null,
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
		if ($this->dispatcher !== null) {
			$this->dispatcher->dispatch(new Events\BeforeMessageHandledEvent($event->getPayload()));
		}

		try {
			$data = Utils\ArrayHash::from(Utils\Json::decode($event->getPayload(), Utils\Json::FORCE_ARRAY)); // @phpstan-ignore-line

			if (
				$data->offsetExists('source')
				&& $data->offsetExists('routing_key')
				&& $data->offsetExists('data')
			) {
				$this->handle(
					MetadataTypes\ModuleSourceType::get($data->offsetGet('source')),
					MetadataTypes\RoutingKeyType::get($data->offsetGet('routing_key')),
					$data->offsetGet('data') // @phpstan-ignore-line
				);

			} else {
				// Log error action reason
				$this->logger->warning('Received message is not in valid format', [
					'source' => 'redisdb-exchange-plugin-publisher',
					'type'   => 'subscribe',
				]);
			}
		} catch (Utils\JsonException $ex) {
			// Log error action reason
			$this->logger->warning('Received message is not valid json', [
				'source'    => 'redisdb-exchange-plugin-publisher',
				'type'      => 'subscribe',
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

		} catch (Exceptions\TerminateException $ex) {
			$event->getClient()->close();
		}

		if ($this->dispatcher !== null) {
			$this->dispatcher->dispatch(new Events\AfterMessageHandledEvent($event->getPayload()));
		}
	}

	/**
	 * @param MetadataTypes\ModuleSourceType $source
	 * @param MetadataTypes\RoutingKeyType $routingKey
	 * @param Utils\ArrayHash $data
	 *
	 * @throws Utils\JsonException
	 */
	private function handle(
		MetadataTypes\ModuleSourceType $source,
		MetadataTypes\RoutingKeyType $routingKey,
		Utils\ArrayHash $data
	): void {
		if ($this->consumer === null) {
			return;
		}

		try {
			$schema = $this->schemaLoader->loadByRoutingKey($routingKey);

		} catch (MetadataExceptions\InvalidArgumentException $ex) {
			return;
		}

		try {
			$data = $this->validator->validate(Utils\Json::encode($this->dataToArray($data)), $schema);

		} catch (Throwable $ex) {
			return;
		}

		try {
			$this->consumer->consume($source, $routingKey, $data);

		} catch (Exceptions\UnprocessableMessageException $ex) {
			// Log error consume reason
			$this->logger->error('Message could not be handled', [
				'source'    => 'redisdb-exchange-plugin-publisher',
				'type'      => 'subscribe',
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			return;
		}
	}

	/**
	 * @param Utils\ArrayHash $data
	 *
	 * @return mixed[]
	 */
	private function dataToArray(Utils\ArrayHash $data): array
	{
		$transformed = (array) $data;

		foreach ($transformed as $key => $value) {
			if ($value instanceof Utils\ArrayHash) {
				$transformed[$key] = $this->dataToArray($value);
			}
		}

		return $transformed;
	}

}
