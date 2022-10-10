<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\RedisDbExchangePlugin\Client;
use FastyBird\RedisDbExchangePlugin\Commands;
use FastyBird\RedisDbExchangePlugin\Connections;
use FastyBird\RedisDbExchangePlugin\Models;
use FastyBird\RedisDbExchangePlugin\Subscribers;
use FastyBird\RedisDbExchangePlugin\Utils;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../BaseTestCase.php';

/**
 * @testCase
 */
final class ExtensionTest extends BaseTestCase
{

	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		Assert::notNull($container->getByType(Connections\Connection::class, false));

		Assert::notNull($container->getByType(Client\Client::class, false));
		Assert::notNull($container->getByType(Client\Factory::class));

		Assert::notNull($container->getByType(Models\StatesManagerFactory::class));
		Assert::notNull($container->getByType(Models\StatesRepositoryFactory::class));

		Assert::notNull($container->getByType(Subscribers\ClientSubscriber::class));

		Assert::notNull($container->getByType(Commands\RedisClient::class));

		Assert::notNull($container->getByType(Utils\IdentifierGenerator::class));
	}

}

$test_case = new ExtensionTest();
$test_case->run();
