<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\RedisDbExchangePlugin;
use FastyBird\RedisDbExchangePlugin\Subscribers;
use FastyBird\WebServer\Events as WebServerEvents;
use Mockery;
use Ninjify\Nunjuck\TestCase\BaseMockeryTestCase;
use React\EventLoop;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class InitializeSubscriberTest extends BaseMockeryTestCase
{

	public function testSubscriberEvents(): void
	{
		$exchange = Mockery::mock(RedisDbExchangePlugin\Exchange::class);

		$eventLoop = Mockery::mock(EventLoop\LoopInterface::class);

		$subscriber = new Subscribers\InitializeSubscriber(
			$exchange,
			$eventLoop,
		);

		Assert::same([WebServerEvents\InitializeEvent::class => 'initialize'], $subscriber->getSubscribedEvents());
	}

	public function testInitialize(): void
	{
		$exchange = Mockery::mock(RedisDbExchangePlugin\Exchange::class);
		$exchange
			->shouldReceive('initializeAsync')
			->withNoArgs()
			->times(1);

		$eventLoop = Mockery::mock(EventLoop\LoopInterface::class);

		$subscriber = new Subscribers\InitializeSubscriber(
			$exchange,
			$eventLoop,
		);

		$subscriber->initialize();

		Assert::true(true);
	}

}

$test_case = new InitializeSubscriberTest();
$test_case->run();
