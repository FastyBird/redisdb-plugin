<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\RedisDbExchangePlugin\Client;
use FastyBird\RedisDbExchangePlugin\Subscribers;
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

		Assert::notNull($container->getByType(Client\Client::class, false));
		Assert::notNull($container->getByType(Client\Factory::class));

		Assert::notNull($container->getByType(Subscribers\ClientSubscriber::class));
	}

}

$test_case = new ExtensionTest();
$test_case->run();
