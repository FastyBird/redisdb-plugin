<?php declare(strict_types = 1);

namespace Tests\Cases\Unit\DI;

use FastyBird\RedisDbPlugin\Client;
use FastyBird\RedisDbPlugin\Commands;
use FastyBird\RedisDbPlugin\Connections;
use FastyBird\RedisDbPlugin\Handlers;
use FastyBird\RedisDbPlugin\Models;
use FastyBird\RedisDbPlugin\Utils;
use Nette;
use Tests\Cases\Unit\BaseTestCase;

final class ExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertNotNull($container->getByType(Connections\Connection::class, false));

		self::assertNotNull($container->getByType(Client\Client::class, false));
		self::assertNotNull($container->getByType(Client\Factory::class, false));

		self::assertNotNull($container->getByType(Models\StatesManagerFactory::class, false));
		self::assertNotNull($container->getByType(Models\StatesRepositoryFactory::class, false));

		self::assertNotNull($container->getByType(Handlers\Message::class, false));

		self::assertNotNull($container->getByType(Commands\RedisClient::class, false));

		self::assertNotNull($container->getByType(Utils\IdentifierGenerator::class, false));
	}

}
