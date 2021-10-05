<?php declare(strict_types = 1);

/**
 * Publisher.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Publisher
 * @since          0.1.0
 *
 * @date           17.09.21
 */

namespace FastyBird\RedisDbExchangePlugin\Publisher;

use FastyBird\DateTimeFactory;
use FastyBird\ModulesMetadata\Types as ModulesMetadataTypes;
use FastyBird\RedisDbExchangePlugin\Client;
use Nette;
use Nette\Utils;
use Psr\Log;

/**
 * Redis DB exchange publisher
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Publisher
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Publisher implements IPublisher
{

	use Nette\SmartObject;

	/** @var Client\IClient */
	private Client\IClient $client;

	/** @var DateTimeFactory\DateTimeFactory */
	private DateTimeFactory\DateTimeFactory $dateTimeFactory;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		Client\IClient $client,
		DateTimeFactory\DateTimeFactory $dateTimeFactory,
		?Log\LoggerInterface $logger = null
	) {
		$this->client = $client;
		$this->dateTimeFactory = $dateTimeFactory;
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * {@inheritDoc}
	 */
	public function publish(
		ModulesMetadataTypes\ModuleOriginType $origin,
		ModulesMetadataTypes\RoutingKeyType $routingKey,
		array $data
	): void {
		try {
			$result = $this->client->publish(
				Utils\Json::encode([
					'sender_id'   => $this->client->getIdentifier(),
					'origin'      => $origin->getValue(),
					'routing_key' => $routingKey->getValue(),
					'created'     => $this->dateTimeFactory->getNow()->format(DATE_ATOM),
					'data'        => $data,
				]),
			);

		} catch (Utils\JsonException $ex) {
			$this->logger->error('[FB:PLUGIN:REDISDB_EXCHANGE] Data could not be converted to message', [
				'message'   => [
					'routingKey' => $routingKey->getValue(),
					'origin'     => $origin->getValue(),
					'data'       => $data,
				],
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			return;
		}

		if ($result) {
			$this->logger->info('[FB:PLUGIN:REDISDB_EXCHANGE] Received message was pushed into data exchange', [
				'message' => [
					'routingKey' => $routingKey->getValue(),
					'origin'     => $origin->getValue(),
					'data'       => $data,
				],
			]);
		} else {
			$this->logger->error('[FB:PLUGIN:REDISDB_EXCHANGE] Received message could not be pushed into data exchange', [
				'message' => [
					'routingKey' => $routingKey->getValue(),
					'origin'     => $origin->getValue(),
					'data'       => $data,
				],
			]);
		}
	}

}
