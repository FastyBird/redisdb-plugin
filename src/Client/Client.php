<?php declare(strict_types = 1);

/**
 * Client.php
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

use FastyBird\RedisDbExchangePlugin\Connections;
use Nette;
use Predis;
use Ramsey\Uuid;

/**
 * Redis database client
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Client
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Client implements IClient
{

	use Nette\SmartObject;

	/** @var string */
	private string $channelName;

	/** @var string */
	private string $identifier;

	/** @var Predis\Client<mixed> */
	private Predis\Client $redis;

	public function __construct(
		string $channelName,
		Connections\IConnection $connection
	) {
		$this->channelName = $channelName;

		$options = [
			'scheme' => 'tcp',
			'host'   => $connection->getHost(),
			'port'   => $connection->getPort(),
		];

		if ($connection->getUsername() !== null) {
			$options['username'] = $connection->getUsername();
		}

		if ($connection->getPassword() !== null) {
			$options['password'] = $connection->getPassword();
		}

		$this->redis = new Predis\Client($options);

		$this->identifier = Uuid\Uuid::uuid4()->toString();
	}

	/**
	 * {@inheritDoc}
	 */
	public function publish(string $content): bool
	{
		$response = $this->redis->publish($this->channelName, $content);

		return $response === 1;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIdentifier(): string
	{
		return $this->identifier;
	}

}
