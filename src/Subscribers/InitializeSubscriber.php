<?php declare(strict_types = 1);

/**
 * InitializeSubscriber.php
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

use FastyBird\RedisDbExchangePlugin;
use FastyBird\WebServer\Events as WebServerEvents;
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
class InitializeSubscriber implements EventDispatcher\EventSubscriberInterface
{

	/** @var RedisDbExchangePlugin\Exchange */
	private RedisDbExchangePlugin\Exchange $exchange;

	/** @var EventLoop\LoopInterface */
	private EventLoop\LoopInterface $eventLoop;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		RedisDbExchangePlugin\Exchange $exchange,
		EventLoop\LoopInterface $eventLoop,
		?Log\LoggerInterface $logger = null
	) {
		$this->exchange = $exchange;
		$this->eventLoop = $eventLoop;

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			WebServerEvents\InitializeEvent::class => 'initialize',
		];
	}

	/**
	 * @return void
	 */
	public function initialize(): void
	{
		try {
			// Prepare exchange
			$this->exchange->initializeAsync();

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
