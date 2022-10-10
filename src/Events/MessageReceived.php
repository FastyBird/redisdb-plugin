<?php declare(strict_types = 1);

/**
 * MessageReceived.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 * @since          0.2.0
 *
 * @date           09.10.21
 */

namespace FastyBird\RedisDbPlugin\Events;

use Clue\React\Redis;
use Symfony\Contracts\EventDispatcher;

/**
 * Exchange message received event
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class MessageReceived extends EventDispatcher\Event
{

	public function __construct(
		private readonly string $channel,
		private readonly string $payload,
		private readonly Redis\Client $client,
	)
	{
	}

	public function getChannel(): string
	{
		return $this->channel;
	}

	public function getPayload(): string
	{
		return $this->payload;
	}

	public function getClient(): Redis\Client
	{
		return $this->client;
	}

}
