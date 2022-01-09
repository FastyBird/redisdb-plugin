<?php declare(strict_types = 1);

/**
 * IPublisher.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Exchange
 * @since          0.9.0
 *
 * @date           09.01.22
 */

namespace FastyBird\RedisDbExchangePlugin\Consumer;

use FastyBird\Metadata\Types as MetadataTypes;
use Nette\Utils;

/**
 * Plugin exchange consumer interface
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Exchange
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IConsumer
{

	/**
	 * @param MetadataTypes\ModuleOriginType $origin
	 * @param MetadataTypes\RoutingKeyType $routingKey
	 * @param Utils\ArrayHash|null $data
	 *
	 * @return void
	 */
	public function consume(
		MetadataTypes\ModuleOriginType $origin,
		MetadataTypes\RoutingKeyType $routingKey,
		?Utils\ArrayHash $data
	): void;

}
