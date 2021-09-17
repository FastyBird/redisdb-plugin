<?php declare(strict_types = 1);

/**
 * IClient.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Client
 * @since          0.1.0
 *
 * @date           17.09.21
 */

namespace FastyBird\RedisDbExchangePlugin\Client;

/**
 * Redis database client interface
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Client
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IClient
{

	/**
	 * @param string $channel
	 * @param string $content
	 *
	 * @return bool
	 */
	public function publish(string $channel, string $content): bool;

}
