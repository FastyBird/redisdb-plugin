<?php declare(strict_types = 1);

/**
 * Startup.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Events
 * @since          0.61.0
 *
 * @date           09.10.22
 */

namespace FastyBird\RedisDbExchangePlugin\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * After message consumed event
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Startup extends EventDispatcher\Event
{

}
