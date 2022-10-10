<?php declare(strict_types = 1);

/**
 * RedisDbPluginExtension.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           25.02.21
 */

namespace FastyBird\RedisDbPlugin\DI;

use FastyBird\RedisDbPlugin\Client;
use FastyBird\RedisDbPlugin\Commands;
use FastyBird\RedisDbPlugin\Connections;
use FastyBird\RedisDbPlugin\Models;
use FastyBird\RedisDbPlugin\Publishers;
use FastyBird\RedisDbPlugin\Subscribers;
use FastyBird\RedisDbPlugin\Utils;
use Nette;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;

/**
 * Message exchange extension container
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisDbPluginExtension extends DI\CompilerExtension
{

	public const NAME = 'fbRedisDbPlugin';

	public static function register(
		Nette\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new RedisDbPluginExtension());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'client' => Schema\Expect::structure([
				'host' => Schema\Expect::string()->default('127.0.0.1'),
				'port' => Schema\Expect::int(6_379),
				'username' => Schema\Expect::string()->nullable(),
				'password' => Schema\Expect::string()->nullable(),
			]),
			'exchange' => Schema\Expect::structure([
				'channel' => Schema\Expect::string()->default('fb_exchange'),
			]),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$publisher = $builder->addDefinition($this->prefix('publisher'), new DI\Definitions\ServiceDefinition())
			->setType(Publishers\Publisher::class)
			->setArguments([
				'channel' => $configuration->exchange->channel,
			]);

		$builder->addDefinition(
			$this->prefix('redis.connection'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Connections\Connection::class)
			->setArguments([
				'host' => $configuration->client->host,
				'port' => $configuration->client->port,
				'username' => $configuration->client->username,
				'password' => $configuration->client->password,
			]);

		$builder->addDefinition(
			$this->prefix('client.sync'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Client\Client::class);

		$builder->addDefinition(
			$this->prefix('client.async.factory'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Client\Factory::class)
			->setArguments([
				'channel' => $configuration->exchange->channel,
				'publisher' => $publisher,
			]);

		// Models

		$builder->addDefinition(
			$this->prefix('model.statesManagerFactory'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\StatesManagerFactory::class);

		$builder->addDefinition(
			$this->prefix('model.statesRepositoryFactory'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\StatesRepositoryFactory::class);

		// Subscribers

		$builder->addDefinition($this->prefix('subscribers.client'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\ClientSubscriber::class);

		// Commands

		$builder->addDefinition($this->prefix('command.client'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\RedisClient::class);

		// Utilities

		$builder->addDefinition($this->prefix('utils.identifier'), new DI\Definitions\ServiceDefinition())
			->setType(Utils\IdentifierGenerator::class);
	}

}
