<?php declare(strict_types = 1);

/**
 * PatternMessageReceived.php
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
 * Exchange pattern message received event
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class PatternMessageReceived extends EventDispatcher\Event
{

	public function __construct(
		private readonly string $pattern,
		private readonly string $channel,
		private readonly string $payload,
		private readonly Redis\Client $client,
	)
	{
	}

	public function getPattern(): string
	{
		return $this->pattern;
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
