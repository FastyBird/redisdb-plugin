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

use FastyBird\ApplicationExchange\Consumer as ApplicationExchangeConsumer;
use FastyBird\RedisDbExchangePlugin;
use FastyBird\RedisDbExchangePlugin\Client;
use FastyBird\RedisDbExchangePlugin\Connections;
use FastyBird\RedisDbExchangePlugin\Consumer;
use FastyBird\RedisDbExchangePlugin\Exceptions;
use FastyBird\RedisDbExchangePlugin\Publisher;
use FastyBird\RedisDbExchangePlugin\Subscribers;
use Nette;
use Nette\DI;
use Nette\Schema;
use stdClass;

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

	/**
	 * @param Nette\Configurator $config
	 * @param string $extensionName
	 *
	 * @return void
	 */
	public static function register(
		Nette\Configurator $config,
		string $extensionName = 'fbRedisDbExchangePlugin'
	): void {
		$config->onCompile[] = function (
			Nette\Configurator $config,
			DI\Compiler $compiler
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new RedisDbExchangePluginExtension());
		};
	}

	/**
	 * {@inheritDoc}
	 */
	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'channel'    => Schema\Expect::string()->default('fb_exchange'),
			'connection' => Schema\Expect::arrayOf(Schema\Expect::structure([
				'host'     => Schema\Expect::string()->default('127.0.0.1'),
				'port'     => Schema\Expect::int(6379),
				'username' => Schema\Expect::string(null)->nullable(),
				'password' => Schema\Expect::string(null)->nullable(),
			])),
			'async'      => Schema\Expect::bool(false),
		]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		/** @var stdClass $configuration */
		$configuration = $this->getConfig();

		$asyncClientService = null;

		foreach ($configuration->connection as $name => $connection) {
			$connectionService = $builder->addDefinition($this->prefix('connection.' . $name))
				->setType(Connections\Connection::class)
				->setArguments([
					'host'     => $connection->host,
					'port'     => $connection->port,
					'username' => $connection->username,
					'password' => $connection->password,
				])
				->setAutowired(false);

			if ($configuration->async === false) {
				$clientService = $builder->addDefinition($this->prefix('client.' . $name))
					->setType(Client\Client::class)
					->setArguments([
						'connection' => $connectionService,
					])
					->setAutowired($name === 'default');

				$builder->addDefinition($this->prefix('publisher.' . $name))
					->setType(Publisher\Publisher::class)
					->setArguments([
						'client' => $clientService,
					])
					->setAutowired(false);
			}

			if ($name === 'default' && $configuration->async) {
				$asyncClientService = $builder->addDefinition($this->prefix('asyncClient'))
					->setType(Client\AsyncClient::class)
					->setArguments([
						'connection' => $connectionService,
					])
					->setAutowired(true);

				$builder->addDefinition($this->prefix('asyncPublisher'))
					->setType(Publisher\AsyncPublisher::class)
					->setArguments([
						'client' => $asyncClientService,
					])
					->setAutowired(false);
			}
		}

		$builder->addDefinition($this->prefix('consumer'))
			->setType(Consumer\ConsumerProxy::class);

		if ($configuration->async) {
			if ($asyncClientService === null) {
				throw new Exceptions\InvalidStateException('Asynchronous client could not be created missing "default" connection configuration');
			}

			$builder->addDefinition($this->prefix('exchange'))
				->setType(RedisDbExchangePlugin\Exchange::class)
				->setArguments([
					'client' => $asyncClientService,
				]);

			$builder->addDefinition($this->prefix('subscribers.initialize'))
				->setType(Subscribers\InitializeSubscriber::class);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/** @var string $consumerProxyServiceName */
		$consumerProxyServiceName = $builder->getByType(Consumer\ConsumerProxy::class, true);

		/** @var DI\Definitions\ServiceDefinition $consumerProxyService */
		$consumerProxyService = $builder->getDefinition($consumerProxyServiceName);

		$consumerServices = $builder->findByType(ApplicationExchangeConsumer\IConsumer::class);

		foreach ($consumerServices as $consumerService) {
			$consumerProxyService->addSetup('?->registerConsumer(?)', [
				'@self',
				$consumerService,
			]);
		}
	}

}
