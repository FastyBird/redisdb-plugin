<?php declare(strict_types = 1);

/**
 * ApplicationSubscriber.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           21.12.20
 */

namespace FastyBird\RedisDbExchangePlugin\Subscribers;

use FastyBird\RedisDbExchangePlugin\Client;
use FastyBird\SocketServerFactory\Events as SocketServerFactoryEvents;
use Psr\Log;
use React\EventLoop;
use Symfony\Component\EventDispatcher;
use Throwable;

/**
 * Redis clients initialization subscriber
 *
 * @package         FastyBird:RedisDbExchangePlugin!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ApplicationSubscriber implements EventDispatcher\EventSubscriberInterface
{

	/** @var Client\IAsyncClient */
	private Client\IAsyncClient $client;

	/** @var EventLoop\LoopInterface */
	private EventLoop\LoopInterface $eventLoop;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		Client\IAsyncClient $client,
		EventLoop\LoopInterface $eventLoop,
		?Log\LoggerInterface $logger = null
	) {
		$this->client = $client;
		$this->eventLoop = $eventLoop;

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * {@inheritDoc}
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			SocketServerFactoryEvents\InitializeEvent::class => 'initialize',
		];
	}

	/**
	 * @return void
	 */
	public function initialize(): void
	{
		try {
			// Prepare exchange
			$this->client->initialize();

		} catch (Throwable $ex) {
			// Log error action reason
			$this->logger->error('Stopping Redis exchange', [
				'source'    => 'redisdb-exchange-plugin-publisher',
				'type'      => 'subscribe',
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			$this->eventLoop->stop();

			return;
		}
	}

}
