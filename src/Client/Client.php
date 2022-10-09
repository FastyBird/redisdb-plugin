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
use Predis\Response as PredisResponse;
use function assert;
use function is_int;

/**
 * Redis database client
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Client
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Client
{

	use Nette\SmartObject;

	/** @var Predis\Client<mixed> */
	private Predis\Client $redis;

	public function __construct(Connections\Connection $connection)
	{
		$options = [
			'scheme' => 'tcp',
			'host' => $connection->getHost(),
			'port' => $connection->getPort(),
		];

		if ($connection->getUsername() !== null) {
			$options['username'] = $connection->getUsername();
		}

		if ($connection->getPassword() !== null) {
			$options['password'] = $connection->getPassword();
		}

		$this->redis = new Predis\Client($options);
	}

	public function publish(string $channel, string $content): bool
	{
		/** @var mixed $response */
		$response = $this->redis->publish($channel, $content);
		assert(is_int($response) || $response instanceof PredisResponse\ResponseInterface);

		return !$response instanceof PredisResponse\ErrorInterface;
	}

}
