<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\RedisDbExchangePlugin\Connections;
use Ninjify\Nunjuck\TestCase\BaseMockeryTestCase;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class ConnectionTest extends BaseMockeryTestCase
{

	public function testDefaultValues(): void
	{
		$config = new Connections\Connection('identifier', '127.0.0.1', 1_234, null, null);

		Assert::same('identifier', $config->getIdentifier());
		Assert::same('127.0.0.1', $config->getHost());
		Assert::same(1234, $config->getPort());
		Assert::null($config->getUsername());
		Assert::null($config->getPassword());
	}

}

$test_case = new ConnectionTest();
$test_case->run();
