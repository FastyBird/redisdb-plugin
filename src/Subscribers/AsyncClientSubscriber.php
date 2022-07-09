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
use FastyBird\Exchange\Entities as ExchangeEntities;
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

	/** @var ExchangeEntities\EntityFactory */
	private ExchangeEntities\EntityFactory $entityFactory;

	/** @var PsrEventDispatcher\EventDispatcherInterface|null */
	private ?PsrEventDispatcher\EventDispatcherInterface $dispatcher;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		ExchangeEntities\EntityFactory $entityFactory,
		?PsrEventDispatcher\EventDispatcherInterface $dispatcher = null,
		?ExchangeConsumer\Consumer $consumer = null,
		?Log\LoggerInterface $logger = null
	) {
		$this->entityFactory = $entityFactory;

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
			$data = Utils\Json::decode($event->getPayload(), Utils\Json::FORCE_ARRAY);

			if (
				is_array($data)
				&& array_key_exists('source', $data)
				&& array_key_exists('routing_key', $data)
				&& array_key_exists('data', $data)
			) {
				$this->handle(
					strval($data['source']),
					MetadataTypes\RoutingKeyType::get($data['routing_key']),
					Utils\Json::encode($data['data'])
				);

			} else {
				// Log error action reason
				$this->logger->warning('Received message is not in valid format', [
					'source' => 'redisdb-exchange-plugin',
					'type'   => 'subscriber',
				]);
			}
		} catch (Utils\JsonException $ex) {
			// Log error action reason
			$this->logger->warning('Received message is not valid json', [
				'source'    => 'redisdb-exchange-plugin',
				'type'      => 'subscriber',
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
	 * @param string $source
	 * @param MetadataTypes\RoutingKeyType $routingKey
	 * @param string $data
	 *
	 * @return void
	 */
	private function handle(
		string $source,
		MetadataTypes\RoutingKeyType $routingKey,
		string $data
	): void {
		if ($this->consumer === null) {
			return;
		}

		$source = $this->validateSource($source);

		if ($source === null) {
			return;
		}

		try {
			$entity = $this->entityFactory->create($data, $routingKey);

		} catch (Throwable $ex) {
			$this->logger->error('Message could not be transformed into entity', [
				'source'    => 'redisdb-exchange-plugin',
				'type'      => 'subscriber',
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			return;
		}

		try {
			$this->consumer->consume($source, $routingKey, $entity);

		} catch (Exceptions\UnprocessableMessageException $ex) {
			// Log error consume reason
			$this->logger->error('Message could not be handled', [
				'source'    => 'redisdb-exchange-plugin',
				'type'      => 'subscriber',
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			return;
		}
	}

	/**
	 * @param string $source
	 *
	 * @return MetadataTypes\ModuleSourceType|MetadataTypes\ConnectorSourceType|MetadataTypes\PluginSourceType|null
	 */
	private function validateSource(
		string $source
	): MetadataTypes\ModuleSourceType|MetadataTypes\ConnectorSourceType|MetadataTypes\PluginSourceType|null {
		if (MetadataTypes\ModuleSourceType::isValidValue($source)) {
			return MetadataTypes\ModuleSourceType::get($source);
		}

		if (MetadataTypes\ConnectorSourceType::isValidValue($source)) {
			return MetadataTypes\ConnectorSourceType::get($source);
		}

		if (MetadataTypes\PluginSourceType::isValidValue($source)) {
			return MetadataTypes\PluginSourceType::get($source);
		}

		return null;
	}

}
