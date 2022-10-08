<?php declare(strict_types = 1);

/**
 * RedisDbExchangePluginExtension.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           25.02.21
 */

namespace FastyBird\RedisDbExchangePlugin\DI;

use FastyBird\RedisDbExchangePlugin\Client;
use FastyBird\RedisDbExchangePlugin\Connections;
use FastyBird\RedisDbExchangePlugin\Exceptions;
use FastyBird\RedisDbExchangePlugin\Publishers;
use FastyBird\RedisDbExchangePlugin\Subscribers;
use Nette;
use Nette\DI;
use Nette\Schema;
use Ramsey\Uuid;
use stdClass;
use function assert;

/**
 * Message exchange extension container
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisDbExchangePluginExtension extends DI\CompilerExtension
{

	public static function register(
		Nette\Configurator $config,
		string $extensionName = 'fbRedisDbExchangePlugin',
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new RedisDbExchangePluginExtension());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'connection' => Schema\Expect::arrayOf(Schema\Expect::structure([
				'host' => Schema\Expect::string()->default('127.0.0.1'),
				'port' => Schema\Expect::int(6_379),
				'username' => Schema\Expect::string()->nullable(),
				'password' => Schema\Expect::string()->nullable(),
				'channel' => Schema\Expect::string()->default('fb_exchange'),
			])),
			'enableClassic' => Schema\Expect::bool(true),
			'enableAsync' => Schema\Expect::bool(false),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$asyncClientService = null;

		foreach ($configuration->connection as $name => $connection) {
			$connectionService = $builder->addDefinition(
				$this->prefix('connection.' . $name),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Connections\Connection::class)
				->setArguments([
					'host' => $connection->host,
					'port' => $connection->port,
					'username' => $connection->username,
					'password' => $connection->password,
					'identifier' => Uuid\Uuid::uuid4()->toString(),
				])
				->setAutowired(false);

			if ($configuration->enableClassic) {
				$clientService = $builder->addDefinition(
					$this->prefix('client.' . $name),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Client\Client::class)
					->setArguments([
						'channelName' => $connection->channel,
						'connection' => $connectionService,
					])
					->setAutowired($name === 'default');

				$builder->addDefinition($this->prefix('publisher.' . $name), new DI\Definitions\ServiceDefinition())
					->setType(Publishers\Publisher::class)
					->setArguments([
						'client' => $clientService,
					]);
			}

			if ($name === 'default' && $configuration->enableAsync) {
				$asyncClientService = $builder->addDefinition(
					$this->prefix('asyncClientFactory'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Client\AsyncClientFactory::class)
					->setArguments([
						'channelName' => $connection->channel,
						'connection' => $connectionService,
					]);
			}
		}

		if ($configuration->enableAsync) {
			if ($asyncClientService === null) {
				throw new Exceptions\InvalidState(
					'Asynchronous client could not be created missing "default" connection configuration',
				);
			}

			$builder->addDefinition($this->prefix('subscribers.asyncClient'), new DI\Definitions\ServiceDefinition())
				->setType(Subscribers\AsyncClientSubscriber::class);
		}
	}

}
