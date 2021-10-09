<?php declare(strict_types = 1);

/**
 * ErrorEvent.php.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Events
 * @since          0.2.0
 *
 * @date           09.10.21
 */

namespace FastyBird\RedisDbExchangePlugin\Events;

use FastyBird\RedisDbExchangePlugin\Client;
use Symfony\Contracts\EventDispatcher;
use Throwable;

/**
 * Connection error event
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ErrorEvent extends EventDispatcher\Event
{

	/** @var Throwable */
	private Throwable $ex;

	/** @var Client\IAsyncClient */
	private Client\IAsyncClient $client;

	public function __construct(Throwable $ex, Client\IAsyncClient $client)
	{
		$this->ex = $ex;
		$this->client = $client;
	}

	/**
	 * @return Throwable
	 */
	public function getException(): Throwable
	{
		return $this->ex;
	}

	/**
	 * @return Client\IAsyncClient
	 */
	public function getClient(): Client\IAsyncClient
	{
		return $this->client;
	}

}
