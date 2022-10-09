<?php declare(strict_types = 1);

/**
 * AsyncClient.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Client
 * @since          0.61.0
 *
 * @date           09.10.22
 */

namespace FastyBird\RedisDbExchangePlugin\Client;

use Clue\React\Redis;
use FastyBird\RedisDbExchangePlugin\Connections;
use FastyBird\RedisDbExchangePlugin\Events;
use FastyBird\RedisDbExchangePlugin\Publishers;
use Psr\EventDispatcher;
use React\EventLoop;
use React\Promise;
use React\Socket;
use Throwable;

final class Factory
{

	public function __construct(
		private readonly string $channel,
		private readonly Connections\Connection $connection,
		private readonly Publishers\Publisher $publisher,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	public function create(
		EventLoop\LoopInterface|null $eventLoop = null,
		Socket\ConnectorInterface|null $connector = null,
	): Promise\PromiseInterface
	{
		$factory = new Redis\Factory($eventLoop, $connector);

		$deferred = new Promise\Deferred();

		$factory->createClient($this->connection->getHost() . ':' . $this->connection->getPort())
			->then(
				function (Redis\Client $redis) use ($deferred): void {
					$this->publisher->setAsyncClient($redis);

					$redis->subscribe($this->channel);

					$redis->on('message', function (string $channel, string $payload) use ($redis): void {
						$this->dispatcher?->dispatch(
							new Events\MessageReceived($channel, $payload, $redis),
						);
					});

					$redis->on(
						'pmessage',
						function (string $pattern, string $channel, string $payload) use ($redis): void {
							$this->dispatcher?->dispatch(
								new Events\PatternMessageReceived(
									$pattern,
									$channel,
									$payload,
									$redis,
								),
							);
						},
					);

					$redis->on('close', function () use ($redis): void {
						$this->dispatcher?->dispatch(new Events\ConnectionClosed($redis));
					});

					$redis->on('error', function (Throwable $ex) use ($redis): void {
						$this->dispatcher?->dispatch(new Events\Error($ex, $redis));
					});

					$deferred->resolve($redis);
				},
				static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				},
			);

		return $deferred->promise();
	}

}
