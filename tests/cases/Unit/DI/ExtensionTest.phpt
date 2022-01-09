<?php declare(strict_types = 1);

namespace Tests\Cases;

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

		Assert::null($container->getByType(Client\IClient::class, false));
		Assert::notNull($container->getByType(Client\IAsyncClient::class));

		Assert::notNull($container->getByType(Subscribers\ApplicationSubscriber::class));
		Assert::notNull($container->getByType(Subscribers\AsyncClientSubscriber::class));
	}

}

$test_case = new ExtensionTest();
$test_case->run();
