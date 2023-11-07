<?php declare(strict_types = 1);

/**
 * StatesManagerFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           02.03.20
 */

namespace FastyBird\Plugin\RedisDb\Models\States;

use FastyBird\DateTimeFactory;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\States;
use Nette;
use Psr\EventDispatcher;
use Psr\Log;
use function class_exists;
use function sprintf;

/**
 * States manager factory
 *
 * @template T of States\State
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Models
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StatesManagerFactory
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Clients\Client $client,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @phpstan-param class-string<T> $entity
	 *
	 * @phpstan-return StatesManager<T>
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function create(string $entity = States\State::class): StatesManager
	{
		if (!class_exists($entity)) {
			throw new Exceptions\InvalidArgument(sprintf('Provided entity class %s does not exists', $entity));
		}

		return new StatesManager($this->client, $this->dateTimeFactory, $entity, $this->dispatcher, $this->logger);
	}

}
