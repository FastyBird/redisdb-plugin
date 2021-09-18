<?php declare(strict_types = 1);

namespace Tests\Cases;

use DateTime;
use FastyBird\DateTimeFactory;
use FastyBird\ModulesMetadata;
use FastyBird\ModulesMetadata\Types as ModulesMetadataTypes;
use FastyBird\RedisDbExchangePlugin\Client;
use FastyBird\RedisDbExchangePlugin\Publisher;
use Mockery;
use Nette\Utils;
use Ninjify\Nunjuck\TestCase\BaseMockeryTestCase;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class PublisherTest extends BaseMockeryTestCase
{

	public function testPublish(): void
	{
		$now = new DateTime();

		$client = Mockery::mock(Client\IClient::class);
		$client
			->shouldReceive('publish')
			->withArgs(function ($data) use ($now): bool {
				Assert::same(Utils\Json::encode([
					'sender_id'   => 'redis_client_identifier',
					'origin'      => ModulesMetadataTypes\ModuleOriginType::TYPE_MODULE_DEVICES_ORIGIN,
					'routing_key' => ModulesMetadata\Constants::MESSAGE_BUS_DEVICES_UPDATED_ENTITY_ROUTING_KEY,
					'created'     => $now->format(DATE_ATOM),
					'data'        => [
						'key_one' => 'value_one',
						'key_two' => 'value_two',
					],
				]), $data);

				return true;
			})
			->andReturn(true)
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

		$publisher = new Publisher\Publisher($client, $dateTimeFactory);

		$publisher->publish(
			ModulesMetadataTypes\ModuleOriginType::TYPE_MODULE_DEVICES_ORIGIN,
			ModulesMetadata\Constants::MESSAGE_BUS_DEVICES_UPDATED_ENTITY_ROUTING_KEY,
			[
				'key_one' => 'value_one',
				'key_two' => 'value_two',
			]
		);
	}

}

$test_case = new PublisherTest();
$test_case->run();
