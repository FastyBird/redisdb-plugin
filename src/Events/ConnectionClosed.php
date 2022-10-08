<?php declare(strict_types = 1);

/**
 * ConnectionClosed.php
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

use Clue\React\Redis;
use Symfony\Contracts\EventDispatcher;

/**
 * After connection closed event
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectionClosed extends EventDispatcher\Event
{

	public function __construct(private readonly Redis\Client $client)
	{
	}

	public function getClient(): Redis\Client
	{
		return $this->client;
	}

}
