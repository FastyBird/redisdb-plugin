<?php declare(strict_types = 1);

/**
 * IPublisher.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Publisher
 * @since          0.1.0
 *
 * @date           17.09.21
 */

namespace FastyBird\RedisDbExchangePlugin\Publisher;

use FastyBird\ApplicationExchange\Publisher as ApplicationExchangePublisher;

/**
 * Redis DB exchange publisher interface
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Publisher
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IPublisher extends ApplicationExchangePublisher\IPublisher
{

}
