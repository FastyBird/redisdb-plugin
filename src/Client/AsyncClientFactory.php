<?php declare(strict_types = 1);

/**
 * AsyncClient.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Client
 * @since          0.1.0
 *
 * @date           17.12.20
 */

namespace FastyBird\RedisDbExchangePlugin\Client;

use Clue\React\Redis;
use FastyBird\RedisDbExchangePlugin\Connections;
use FastyBird\RedisDbExchangePlugin\Events;
use Psr\EventDispatcher;
use React\EventLoop;
use React\Promise;
use React\Socket;
use Throwable;

final class AsyncClientFactory
{

	public function __construct(
		private readonly string $channelName,
		private readonly Connections\Connection $connection,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	public function create(
		Socket\ConnectorInterface|null $connector,
		EventLoop\LoopInterface|null $eventLoop,
	): Promise\PromiseInterface
	{
		$factory = new Redis\Factory($eventLoop, $connector);

		$deferred = new Promise\Deferred();

		$factory->createClient($this->connection->getHost() . ':' . $this->connection->getPort())
			->then(function (Redis\Client $redis) use ($deferred): void {
				// @phpstan-ignore-next-line
				$redis->subscribe($this->channelName);

				$redis->on('message', function (string $channel, string $payload) use ($redis): void {
					$this->dispatcher?->dispatch(new Events\MessageReceived($channel, $payload, $redis));
				});

				$redis->on(
					'pmessage',
					function (string $pattern, string $channel, string $payload) use ($redis): void {
						$this->dispatcher?->dispatch(
							new Events\PatternMessageReceived($pattern, $channel, $payload, $redis),
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
			})
			->otherwise(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

}
