<?php declare(strict_types = 1);

/**
 * IAsyncPublisher.php
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

namespace FastyBird\RedisDbExchangePlugin\Publishers;

use FastyBird\Exchange\Publisher as ExchangePublisher;

/**
 * Redis publisher
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Exchange
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IAsyncPublisher extends ExchangePublisher\IPublisher
{

}
