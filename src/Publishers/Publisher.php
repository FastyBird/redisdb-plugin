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
use FastyBird\ExchangePlugin\Publisher as ExchangePluginPublisher;
use FastyBird\ModulesMetadata\Types as ModulesMetadataTypes;
use FastyBird\RedisDbExchangePlugin\Client;
use Nette;
use Nette\Utils;
use Psr\Log;

/**
 * Redis DB exchange publisher
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Publishers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Publisher implements ExchangePluginPublisher\IPublisher
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
		?Utils\ArrayHash $data
	): void {
		try {
			$result = $this->client->publish(
				Utils\Json::encode([
					'sender_id'   => $this->client->getIdentifier(),
					'origin'      => $origin->getValue(),
					'routing_key' => $routingKey->getValue(),
					'created'     => $this->dateTimeFactory->getNow()->format(DATE_ATOM),
					'data'        => $data !== null ? $this->dataToArray($data) : null,
				]),
			);

		} catch (Utils\JsonException $ex) {
			$this->logger->error('[FB:PLUGIN:REDISDB_EXCHANGE] Data could not be converted to message', [
				'message'   => [
					'routingKey' => $routingKey->getValue(),
					'origin'     => $origin->getValue(),
					'data'       => $data !== null ? $this->dataToArray($data) : null,
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
					'data'       => $data !== null ? $this->dataToArray($data) : null,
				],
			]);
		} else {
			$this->logger->error('[FB:PLUGIN:REDISDB_EXCHANGE] Received message could not be pushed into data exchange', [
				'message' => [
					'routingKey' => $routingKey->getValue(),
					'origin'     => $origin->getValue(),
					'data'       => $data !== null ? $this->dataToArray($data) : null,
				],
			]);
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
