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
use function array_key_exists;
use function is_array;
use function strval;

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

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly ExchangeEntities\EntityFactory $entityFactory,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		private readonly ExchangeConsumer\Consumer|null $consumer = null,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\MessageReceived::class => 'handleMessage',
		];
	}

	public function handleMessage(Events\MessageReceived $event): void
	{
		$this->dispatcher?->dispatch(new Events\BeforeMessageHandled($event->getPayload()));

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
					MetadataTypes\RoutingKey::get($data['routing_key']),
					Utils\Json::encode($data['data']),
				);

			} else {
				// Log error action reason
				$this->logger->warning('Received message is not in valid format', [
					'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_EXCHANGE_REDISDB,
					'type' => 'subscriber',
				]);
			}
		} catch (Utils\JsonException $ex) {
			// Log error action reason
			$this->logger->warning('Received message is not valid json', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_EXCHANGE_REDISDB,
				'type' => 'subscriber',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

		} catch (Exceptions\Terminate) {
			$event->getClient()->close();
		}

		$this->dispatcher?->dispatch(new Events\AfterMessageHandled($event->getPayload()));
	}

	private function handle(
		string $source,
		MetadataTypes\RoutingKey $routingKey,
		string $data,
	): void
	{
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
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_EXCHANGE_REDISDB,
				'type' => 'subscriber',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			return;
		}

		try {
			$this->consumer->consume($source, $routingKey, $entity);

		} catch (Exceptions\UnprocessableMessage $ex) {
			// Log error consume reason
			$this->logger->error('Message could not be handled', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_EXCHANGE_REDISDB,
				'type' => 'subscriber',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			return;
		}
	}

	private function validateSource(
		string $source,
	): MetadataTypes\ModuleSource|MetadataTypes\ConnectorSource|MetadataTypes\PluginSource|null
	{
		if (MetadataTypes\ModuleSource::isValidValue($source)) {
			return MetadataTypes\ModuleSource::get($source);
		}

		if (MetadataTypes\ConnectorSource::isValidValue($source)) {
			return MetadataTypes\ConnectorSource::get($source);
		}

		if (MetadataTypes\PluginSource::isValidValue($source)) {
			return MetadataTypes\PluginSource::get($source);
		}

		return null;
	}

}
