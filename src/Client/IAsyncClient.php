<?php declare(strict_types = 1);

/**
 * IAsyncClient.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Client
 * @since          0.1.0
 *
 * @date           17.12.20
 */

namespace FastyBird\RedisDbExchangePlugin\Client;

use React\Promise;
use Throwable;

/**
 * Redis exchange asynchronous client interface
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Client
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @method onOpen(IAsyncClient $client)
 * @method onClose(IAsyncClient $client)
 * @method onMessage(string $channel, string $payload, IAsyncClient $client)
 * @method onPmessage(string $patern, string $channel, string $payload, IAsyncClient $client)
 * @method onError(Throwable $ex, IAsyncClient $client)
 */
interface IAsyncClient
{

	/**
	 * @return Promise\ExtendedPromiseInterface
	 */
	public function connect(): Promise\ExtendedPromiseInterface;

	/**
	 * @return void
	 */
	public function disconnect(): void;

	/**
	 * @return void
	 */
	public function close(): void;

	/**
	 * @return void
	 *
	 * @throws Throwable
	 */
	public function initialize(): void;

	/**
	 * @return string
	 */
	public function getIdentifier(): string;

}
