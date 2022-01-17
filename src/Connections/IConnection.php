<?php declare(strict_types = 1);

/**
 * IConnection.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Connections
 * @since          0.1.0
 *
 * @date           08.03.20
 */

namespace FastyBird\RedisDbExchangePlugin\Connections;

/**
 * Redis connection configuration interface
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Connections
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IConnection
{

	/**
	 * @return string
	 */
	public function getHost(): string;

	/**
	 * @return int
	 */
	public function getPort(): int;

	/**
	 * @return string|null
	 */
	public function getUsername(): ?string;

	/**
	 * @return string|null
	 */
	public function getPassword(): ?string;

	/**
	 * @return string
	 */
	public function getIdentifier(): string;

}
