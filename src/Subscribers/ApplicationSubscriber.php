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

use FastyBird\ApplicationExchange\Events as ApplicationExchangeEvents;
use FastyBird\RedisDbExchangePlugin\Client;
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
	 * @return array<string, mixed>
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			ApplicationExchangeEvents\ApplicationInitializeEvent::class => 'initialize',
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
			$this->logger->error('[FB:PLUGIN:REDISDB_EXCHANGE] Stopping Redis exchange', [
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
