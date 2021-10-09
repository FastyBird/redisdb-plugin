<?php declare(strict_types = 1);

/**
 * PatternMessageReceivedEvent.php.php
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
 * Exchange pattern message received event
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class PatternMessageReceivedEvent extends EventDispatcher\Event
{

	/** @var string */
	private string $pattern;

	/** @var string */
	private string $channel;

	/** @var string */
	private string $payload;

	/** @var Client\IAsyncClient */
	private Client\IAsyncClient $client;

	public function __construct(string $pattern, string $channel, string $payload, Client\IAsyncClient $client)
	{
		$this->pattern = $pattern;
		$this->channel = $channel;
		$this->payload = $payload;
		$this->client = $client;
	}

	/**
	 * @return string
	 */
	public function getPattern(): string
	{
		return $this->pattern;
	}

	/**
	 * @return string
	 */
	public function getChannel(): string
	{
		return $this->channel;
	}

	/**
	 * @return string
	 */
	public function getPayload(): string
	{
		return $this->payload;
	}

	/**
	 * @return Client\IAsyncClient
	 */
	public function getClient(): Client\IAsyncClient
	{
		return $this->client;
	}

}
