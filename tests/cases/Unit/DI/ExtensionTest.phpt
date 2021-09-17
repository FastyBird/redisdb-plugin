<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\RedisDbExchangePlugin;
use FastyBird\RedisDbExchangePlugin\Connections;
use FastyBird\RedisDbExchangePlugin\Consumer;
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

		Assert::notNull($container->getByType(RedisDbExchangePlugin\Exchange::class));

		Assert::notNull($container->getByType(Consumer\IConsumer::class));

		Assert::notNull($container->getByType(Subscribers\InitializeSubscriber::class));
	}

}

$test_case = new ExtensionTest();
$test_case->run();
