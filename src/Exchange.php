<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     common
 * @since          0.1.0
 *
 * @date           10.07.20
 */

namespace FastyBird\RedisDbExchangePlugin;

use Closure;
use FastyBird\RedisDbExchangePlugin;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\Promise;
use Throwable;

/**
 * Redis exchange builder
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @method onBeforeConsumeMessage(string $payload)
 * @method onAfterConsumeMessage(string $payload)
 */
final class Exchange
{

	use Nette\SmartObject;

	/** @var Closure[] */
	public array $onBeforeConsumeMessage = [];

	/** @var Closure[] */
	public array $onAfterConsumeMessage = [];

	/** @var Consumer\IConsumer */
	private Consumer\IConsumer $consumer;

	/** @var Client\IAsyncClient */
	private Client\IAsyncClient $asyncClient;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	/**
	 * @param Client\IAsyncClient $client
	 * @param Consumer\IConsumer $consumer
	 * @param Log\LoggerInterface|null $logger
	 */
	public function __construct(
		Client\IAsyncClient $client,
		Consumer\IConsumer $consumer,
		?Log\LoggerInterface $logger = null
	) {
		$this->asyncClient = $client;
		$this->consumer = $consumer;

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @return void
	 *
	 * @throws Throwable
	 */
	public function initializeAsync(): void
	{
		$promise = $this->asyncClient
			->connect()
			->then(function (Client\AsyncClient $client): void {
				$client->subscribe(RedisDbExchangePlugin\Constants::EXCHANGE_CHANNEL);

				$client->onMessage[] = function (string $channel, string $payload) use ($client): void {
					if ($channel === RedisDbExchangePlugin\Constants::EXCHANGE_CHANNEL) {
						$this->onBeforeConsumeMessage($payload);

						try {
							$data = Utils\ArrayHash::from(Utils\Json::decode($payload, Utils\Json::FORCE_ARRAY));

							if (
								$data->offsetExists('origin')
								&& $data->offsetExists('routing_key')
								&& $data->offsetExists('data')
							) {
								$this->consumer->consume(
									$data->offsetGet('origin'),
									$data->offsetGet('routing_key'),
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
							$client->close();
						}

						$this->onAfterConsumeMessage($payload);
					}
				};
			});

		if ($promise instanceof Promise\ExtendedPromiseInterface) {
			$promise->done();
		}
	}

}
