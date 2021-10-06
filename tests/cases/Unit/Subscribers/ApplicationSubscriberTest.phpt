<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\RedisDbExchangePlugin\Client;
use FastyBird\RedisDbExchangePlugin\Subscribers;
use FastyBird\SocketServerFactory\Events as SocketServerFactoryEvents;
use Mockery;
use Ninjify\Nunjuck\TestCase\BaseMockeryTestCase;
use React\EventLoop;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class ApplicationSubscriberTest extends BaseMockeryTestCase
{

	public function testSubscriberEvents(): void
	{
		$asyncClient = Mockery::mock(Client\IAsyncClient::class);

		$eventLoop = Mockery::mock(EventLoop\LoopInterface::class);

		$subscriber = new Subscribers\ApplicationSubscriber(
			$asyncClient,
			$eventLoop,
		);

		Assert::same([SocketServerFactoryEvents\InitializeEvent::class => 'initialize'], $subscriber->getSubscribedEvents());
	}

	public function testInitialize(): void
	{
		$asyncClient = Mockery::mock(Client\IAsyncClient::class);
		$asyncClient
			->shouldReceive('initialize')
			->withNoArgs()
			->times(1);

		$eventLoop = Mockery::mock(EventLoop\LoopInterface::class);

		$subscriber = new Subscribers\ApplicationSubscriber(
			$asyncClient,
			$eventLoop,
		);

		$subscriber->initialize();

		Assert::true(true);
	}

}

$test_case = new ApplicationSubscriberTest();
$test_case->run();
