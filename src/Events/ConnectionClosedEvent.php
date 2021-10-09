<?php declare(strict_types = 1);

/**
 * ConnectionClosedEvent.php.php
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

/**
 * After connection closed event
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectionClosedEvent extends EventDispatcher\Event
{

	/** @var Client\IAsyncClient */
	private Client\IAsyncClient $client;

	public function __construct(Client\IAsyncClient $client)
	{
		$this->client = $client;
	}

	/**
	 * @return Client\IAsyncClient
	 */
	public function getClient(): Client\IAsyncClient
	{
		return $this->client;
	}

}
