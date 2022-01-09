<?php declare(strict_types = 1);

namespace Tests\Cases;

use DateTime;
use FastyBird\DateTimeFactory;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\RedisDbExchangePlugin\Client;
use FastyBird\RedisDbExchangePlugin\Publishers;
use Mockery;
use Nette\Utils;
use Ninjify\Nunjuck\TestCase\BaseMockeryTestCase;
use React\Promise;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class AsyncPublisherTest extends BaseMockeryTestCase
{

	public function testPublish(): void
	{
		$now = new DateTime();

		$request = new Promise\Deferred();
		$promise = $request->promise();

		$asyncClient = Mockery::mock(Client\IAsyncClient::class);
		$asyncClient
			->shouldReceive('publish')
			->withArgs(function ($data) use ($now): bool {
				Assert::same(Utils\Json::encode([
					'sender_id'   => 'redis_client_identifier',
					'origin'      => MetadataTypes\ModuleOriginType::ORIGIN_MODULE_DEVICES,
					'routing_key' => MetadataTypes\RoutingKeyType::ROUTE_DEVICES_ENTITY_UPDATED,
					'created'     => $now->format(DATE_ATOM),
					'data'        => [
						'key_one' => 'value_one',
						'key_two' => 'value_two',
					],
				]), $data);

				return true;
			})
			->andReturn($promise)
			->times(1)
			->getMock()
			->shouldReceive('getIdentifier')
			->withNoArgs()
			->andReturn('redis_client_identifier')
			->times(1);

		$dateTimeFactory = Mockery::mock(DateTimeFactory\DateTimeFactory::class);
		$dateTimeFactory
			->shouldReceive('getNow')
			->withNoArgs()
			->andReturn($now)
			->times(1);

		$publisher = new Publishers\AsyncPublisher($asyncClient, $dateTimeFactory);

		$publisher->publish(
			MetadataTypes\ModuleOriginType::get(MetadataTypes\ModuleOriginType::ORIGIN_MODULE_DEVICES),
			MetadataTypes\RoutingKeyType::get(MetadataTypes\RoutingKeyType::ROUTE_DEVICES_ENTITY_UPDATED),
			Utils\ArrayHash::from([
				'key_one' => 'value_one',
				'key_two' => 'value_two',
			])
		);
	}

}

$test_case = new AsyncPublisherTest();
$test_case->run();
