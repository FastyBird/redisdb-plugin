<?php declare(strict_types = 1);

/**
 * Configuration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Connections
 * @since          1.0.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Plugin\RedisDb\Connections;

use Nette;

/**
 * Redis connection configuration
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Connections
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Configuration
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $host = '127.0.0.1',
		private readonly int $port = 6_379,
		private readonly string|null $username = null,
		private readonly string|null $password = null,
	)
	{
	}

	public function getHost(): string
	{
		return $this->host;
	}

	public function getPort(): int
	{
		return $this->port;
	}

	public function getUsername(): string|null
	{
		return $this->username;
	}

	public function getPassword(): string|null
	{
		return $this->password;
	}

}
