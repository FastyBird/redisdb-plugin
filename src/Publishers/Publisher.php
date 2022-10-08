<?php declare(strict_types = 1);

/**
 * Publishers.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Publishers
 * @since          0.1.0
 *
 * @date           17.09.21
 */

namespace FastyBird\RedisDbExchangePlugin\Publishers;

use FastyBird\DateTimeFactory;
use FastyBird\Exchange\Publisher as ExchangePublisher;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\RedisDbExchangePlugin\Client;
use Nette;
use Nette\Utils;
use Psr\Log;
use const DATE_ATOM;

/**
 * Redis DB exchange publisher
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Publishers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Publisher implements ExchangePublisher\Publisher
{

	use Nette\SmartObject;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Client\Client $client,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function publish(
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataEntities\Entity|null $entity,
	): void
	{
		try {
			$result = $this->client->publish(
				Utils\Json::encode([
					'sender_id' => $this->client->getIdentifier(),
					'source' => $source->getValue(),
					'routing_key' => $routingKey->getValue(),
					'created' => $this->dateTimeFactory->getNow()->format(DATE_ATOM),
					'data' => $entity?->toArray(),
				]),
			);

		} catch (Utils\JsonException $ex) {
			$this->logger->error('Data could not be converted to message', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_EXCHANGE_REDISDB,
				'type' => 'publish',
				'message' => [
					'routingKey' => $routingKey->getValue(),
					'source' => $source->getValue(),
					'data' => $entity?->toArray(),
				],
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			return;
		}

		if ($result) {
			$this->logger->debug('Received message was pushed into data exchange', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_EXCHANGE_REDISDB,
				'type' => 'publish',
				'message' => [
					'routingKey' => $routingKey->getValue(),
					'source' => $source->getValue(),
					'data' => $entity?->toArray(),
				],
			]);
		} else {
			$this->logger->error('Received message could not be pushed into data exchange', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_EXCHANGE_REDISDB,
				'type' => 'publish',
				'message' => [
					'routingKey' => $routingKey->getValue(),
					'source' => $source->getValue(),
					'data' => $entity?->toArray(),
				],
			]);
		}
	}

}
