<?php declare(strict_types = 1);

/**
 * BeforeMessageHandled.php
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

use Symfony\Contracts\EventDispatcher;

/**
 * Before message handled event
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class BeforeMessageHandled extends EventDispatcher\Event
{

	public function __construct(private readonly string $payload)
	{
	}

	public function getPayload(): string
	{
		return $this->payload;
	}

}