<?php declare(strict_types = 1);

/**
 * IConsumer.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Consumer
 * @since          0.1.0
 *
 * @date           17.09.21
 */

namespace FastyBird\RedisDbExchangePlugin\Consumer;

use FastyBird\ApplicationExchange\Consumer as ApplicationExchangeConsumer;
use Nette\Utils;

/**
 * Exchange messages consumer interface
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Consumer
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IConsumer
{

	/**
	 * @param ApplicationExchangeConsumer\IConsumer $consumer
	 *
	 * @return void
	 */
	public function registerConsumer(ApplicationExchangeConsumer\IConsumer $consumer): void;

	/**
	 * @param string $origin
	 * @param string $routingKey
	 * @param Utils\ArrayHash $data
	 *
	 * @return void
	 */
	public function consume(
		string $origin,
		string $routingKey,
		Utils\ArrayHash $data
	): void;

}
