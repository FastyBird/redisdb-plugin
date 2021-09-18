<?php declare(strict_types = 1);

/**
 * AsyncPublisher.php
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
use FastyBird\RedisDbExchangePlugin;
use FastyBird\RedisDbExchangePlugin\Client;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\Promise;

/**
 * Redis DB exchange asynchronous publisher
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Publisher
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AsyncPublisher implements IPublisher
{

	use Nette\SmartObject;

	/** @var Client\IAsyncClient */
	private Client\IAsyncClient $client;

	/** @var DateTimeFactory\DateTimeFactory */
	private DateTimeFactory\DateTimeFactory $dateTimeFactory;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		Client\IAsyncClient $client,
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
		string $origin,
		string $routingKey,
		array $data
	): void {
		try {
			// Compose message
			$message = Utils\Json::encode($data);

		} catch (Utils\JsonException $ex) {
			$this->logger->error('[FB:PLUGIN:REDISDB_EXCHANGE] Data could not be converted to message', [
				'message'   => [
					'routingKey' => $routingKey,
					'origin' => $origin,
				],
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			return;
		}

		try {
			$result = $this->client->publish(
				RedisDbExchangePlugin\Constants::EXCHANGE_CHANNEL,
				Utils\Json::encode([
					'origin'      => $origin,
					'routing_key' => $routingKey,
					'created'     => $this->dateTimeFactory->getNow()->format(DATE_ATOM),
					'data'        => $data,
				]),
			);

			$result->then(function () use ($routingKey, $origin, $message): void {
				$this->logger->info('[FB:PLUGIN:REDISDB_EXCHANGE] Received message was pushed into data exchange', [
					'message' => [
						'routingKey' => $routingKey,
						'origin'     => $origin,
						'body'       => $message,
					],
				]);
			});

			if ($result instanceof Promise\ExtendedPromiseInterface) {
				$result->otherwise(function () use ($routingKey, $origin, $message): void {
					$this->logger->error('[FB:PLUGIN:REDISDB_EXCHANGE] Received message could not be pushed into data exchange', [
						'message' => [
							'routingKey' => $routingKey,
							'origin'     => $origin,
							'body'       => $message,
						],
					]);
				});
			}
		} catch (Utils\JsonException $ex) {
			$this->logger->error('[FB:PLUGIN:REDISDB_EXCHANGE] Data could not be converted to message', [
				'message'   => [
					'routingKey' => $routingKey,
					'origin'     => $origin,
					'body'       => $message,
				],
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			return;
		}
	}

}
