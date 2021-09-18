<?php declare(strict_types = 1);

/**
 * Connection.php
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

use Nette;

/**
 * Redis connection configuration
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Connections
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connection implements IConnection
{

	use Nette\SmartObject;

	/** @var string */
	private string $host;

	/** @var int */
	private int $port;

	/** @var string|null */
	private ?string $username;

	/** @var string|null */
	private ?string $password;

	public function __construct(
		string $host = '127.0.0.1',
		int $port = 6379,
		?string $username = null,
		?string $password = null
	) {
		$this->host = $host;
		$this->port = $port;
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getHost(): string
	{
		return $this->host;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPort(): int
	{
		return $this->port;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getUsername(): ?string
	{
		return $this->username;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPassword(): ?string
	{
		return $this->password;
	}

}
