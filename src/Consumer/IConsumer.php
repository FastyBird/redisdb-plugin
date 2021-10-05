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

use FastyBird\ExchangePlugin\Consumer as ExchangePluginConsumer;
use FastyBird\ModulesMetadata\Types as ModulesMetadataTypes;
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
	 * @param ExchangePluginConsumer\IConsumer $consumer
	 *
	 * @return void
	 */
	public function registerConsumer(ExchangePluginConsumer\IConsumer $consumer): void;

	/**
	 * @param ModulesMetadataTypes\ModuleOriginType $origin
	 * @param ModulesMetadataTypes\RoutingKeyType $routingKey
	 * @param Utils\ArrayHash $data
	 *
	 * @return void
	 */
	public function consume(
		ModulesMetadataTypes\ModuleOriginType $origin,
		ModulesMetadataTypes\RoutingKeyType $routingKey,
		Utils\ArrayHash $data
	): void;

}
