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
use FastyBird\RedisDbExchangePlugin\Commands;
use FastyBird\RedisDbExchangePlugin\Connections;
use FastyBird\RedisDbExchangePlugin\Publishers;
use FastyBird\RedisDbExchangePlugin\Subscribers;
use FastyBird\RedisDbExchangePlugin\Utils;
use Nette;
use Nette\DI;
use Nette\Schema;
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

	public const NAME = 'fbRedisDbExchangePlugin';

	public static function register(
		Nette\Configurator $config,
		string $extensionName = self::NAME,
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

		$connectionService = $builder->addDefinition(
			$this->prefix('connection'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Connections\Connection::class)
			->setArguments([
				'host' => $configuration->client->host,
				'port' => $configuration->client->port,
				'username' => $configuration->client->username,
				'password' => $configuration->client->password,
			])
			->setAutowired(false);

		$clientService = $builder->addDefinition(
			$this->prefix('client'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Client\Client::class)
			->setArguments([
				'connection' => $connectionService,
			]);

		$publisher = $builder->addDefinition($this->prefix('publisher'), new DI\Definitions\ServiceDefinition())
			->setType(Publishers\Publisher::class)
			->setArguments([
				'channel' => $configuration->exchange->channel,
				'client' => $clientService,
			]);

		$builder->addDefinition(
			$this->prefix('asyncClientFactory'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Client\Factory::class)
			->setArguments([
				'channel' => $configuration->exchange->channel,
				'connection' => $connectionService,
				'publisher' => $publisher,
			]);

		$builder->addDefinition($this->prefix('subscribers.asyncClient'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\ClientSubscriber::class);

		$builder->addDefinition($this->prefix('command.client'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\RedisClient::class);

		$builder->addDefinition($this->prefix('utils.identifier'), new DI\Definitions\ServiceDefinition())
			->setType(Utils\IdentifierGenerator::class);
	}

}
