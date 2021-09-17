<?php declare(strict_types = 1);

/**
 * UnprocessableMessageException.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           25.02.21
 */

namespace FastyBird\RedisDbExchangePlugin\Exceptions;

class UnprocessableMessageException extends InvalidStateException implements IException
{

}
