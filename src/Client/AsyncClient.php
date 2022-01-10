<?php declare(strict_types = 1);

/**
 * AsyncClient.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Client
 * @since          0.1.0
 *
 * @date           17.12.20
 */

namespace FastyBird\RedisDbExchangePlugin\Client;

use Clue\Redis\Protocol as RedisProtocol;
use FastyBird\RedisDbExchangePlugin\Connections;
use FastyBird\RedisDbExchangePlugin\Events;
use FastyBird\RedisDbExchangePlugin\Exceptions;
use Nette;
use Psr\EventDispatcher;
use Psr\Log;
use Ramsey\Uuid;
use React\EventLoop;
use React\Promise;
use React\Socket;
use Throwable;
use UnderflowException;

/**
 * Redis exchange asynchronous client
 *
 * @package        FastyBird:RedisDbExchangePlugin!
 * @subpackage     Client
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class AsyncClient implements IAsyncClient
{

	use Nette\SmartObject;

	/** @var string */
	private string $channelName;

	/** @var string */
	private string $identifier;

	/** @var bool */
	private bool $isConnected = false;

	/** @var bool */
	private bool $isConnecting = false;

	/** @var bool */
	private bool $closing = false;

	/** @var int */
	private int $timeout = 5;

	/** @var Connections\IConnection */
	private Connections\IConnection $connection;

	/** @var Promise\Deferred[] */
	private array $requests = [];

	/** @var Socket\ConnectionInterface|null */
	private ?Socket\ConnectionInterface $stream = null;

	/** @var RedisProtocol\Parser\ParserInterface */
	private RedisProtocol\Parser\ParserInterface $parser;

	/** @var RedisProtocol\Serializer\SerializerInterface */
	private RedisProtocol\Serializer\SerializerInterface $serializer;

	/** @var EventDispatcher\EventDispatcherInterface|null */
	private ?EventDispatcher\EventDispatcherInterface $dispatcher;

	/** @var EventLoop\LoopInterface */
	private EventLoop\LoopInterface $eventLoop;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		string $channelName,
		Connections\IConnection $connection,
		EventLoop\LoopInterface $eventLoop,
		?EventDispatcher\EventDispatcherInterface $dispatcher = null,
		?Log\LoggerInterface $logger = null
	) {
		$this->channelName = $channelName;

		$this->connection = $connection;

		$factory = new RedisProtocol\Factory();

		$this->parser = $factory->createResponseParser();
		$this->serializer = $factory->createSerializer();

		$this->eventLoop = $eventLoop;

		$this->dispatcher = $dispatcher;

		$this->logger = $logger ?? new Log\NullLogger();

		$this->identifier = Uuid\Uuid::uuid4()->toString();
	}

	/**
	 * {@inheritDoc}
	 */
	public function disconnect(): void
	{
		$this->closing = true;

		if ($this->requests === []) {
			$this->close();
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function close(): void
	{
		if (!$this->isConnected) {
			return;
		}

		$this->closing = true;
		$this->isConnected = false;

		if ($this->stream !== null) {
			$this->stream->close();
		}

		if ($this->dispatcher !== null) {
			$this->dispatcher->dispatch(new Events\ConnectionClosedEvent($this));
		}

		// Reject all remaining requests in the queue
		while ($this->requests) {
			$request = array_shift($this->requests);
			$request->reject(new Exceptions\RuntimeException('Connection closing'));
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function unsubscribe(string $channel): Promise\PromiseInterface
	{
		$request = new Promise\Deferred();
		$promise = $request->promise();

		if ($this->stream === null || $this->closing) {
			$request->reject(new Exceptions\RuntimeException('Connection closed'));

			return $promise;
		}

		$this->stream->write($this->serializer->getRequestMessage('unsubscribe', [$channel]));

		$this->requests[] = $request;

		return $promise;
	}

	/**
	 * {@inheritDoc}
	 */
	public function publish(string $content): Promise\PromiseInterface
	{
		$request = new Promise\Deferred();
		$promise = $request->promise();

		if ($this->stream === null || $this->closing) {
			$request->reject(new Exceptions\RuntimeException('Connection closed'));

			return $promise;
		}

		$this->stream->write($this->serializer->getRequestMessage('publish', [$this->channelName, $content]));

		$this->requests[] = $request;

		return $promise;
	}

	/**
	 * {@inheritDoc}
	 */
	public function initialize(): void
	{
		$promise = $this
			->connect()
			->then(function (AsyncClient $client): void {
				$client->subscribe($this->channelName);
			});

		if ($promise instanceof Promise\ExtendedPromiseInterface) {
			$promise->done();
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	public function connect(): Promise\ExtendedPromiseInterface
	{
		$this->closing = false;

		$deferred = new Promise\Deferred();

		if ($this->isConnected || $this->isConnecting) {
			$deferred->reject(new Exceptions\LogicException('The redis client is already connected.'));

			/** @var Promise\ExtendedPromiseInterface $promise */
			$promise = $deferred->promise();

			return $promise;
		}

		$connector = $this->createConnector();

		$this->establishConnection($connector)
			->then(
				function (Socket\ConnectionInterface $stream) use ($deferred): void {
					$this->stream = $stream;

					if ($this->dispatcher !== null) {
						$this->dispatcher->dispatch(new Events\ConnectionOpenedEvent($this));
					}

					$deferred->resolve($this);
				},
				function (Throwable $ex) use ($deferred): void {
					$this->isConnecting = false;

					if ($this->dispatcher !== null) {
						$this->dispatcher->dispatch(new Events\ErrorEvent($ex, $this));
					}

					$deferred->reject($ex);
				}
			);

		/** @var Promise\ExtendedPromiseInterface $promise */
		$promise = $deferred->promise();

		return $promise;
	}

	/**
	 * @return Socket\ConnectorInterface
	 */
	private function createConnector(): Socket\ConnectorInterface
	{
		return new Socket\Connector($this->eventLoop);
	}

	/**
	 * Establishes a network connection to a server
	 *
	 * @param Socket\ConnectorInterface $connector
	 *
	 * @return Promise\PromiseInterface
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	private function establishConnection(Socket\ConnectorInterface $connector): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$timer = $this->eventLoop->addTimer($this->timeout, function () use ($deferred): void {
			$exception = new Exceptions\RuntimeException(sprintf('Connection timed out after %d seconds.', $this->timeout));

			$deferred->reject($exception);
		});

		$connector->connect($this->connection->getHost() . ':' . $this->connection->getPort())
			->then(
				function (Socket\ConnectionInterface $stream) use ($deferred, $timer): void {
					$this->eventLoop->cancelTimer($timer);

					$stream->on('data', function ($chunk): void {
						try {
							$models = $this->parser->pushIncoming($chunk);

						} catch (RedisProtocol\Parser\ParserException $ex) {
							if ($this->dispatcher !== null) {
								$this->dispatcher->dispatch(new Events\ErrorEvent($ex, $this));
							}

							$this->close();

							return;
						}

						foreach ($models as $data) {
							try {
								$this->handleMessage($data);

							} catch (UnderflowException $ex) {
								if ($this->dispatcher !== null) {
									$this->dispatcher->dispatch(new Events\ErrorEvent($ex, $this));
								}

								$this->close();

								return;
							}
						}
					});

					$stream->on('close', function (): void {
						$this->close();
					});

					$stream->on('error', function (Throwable $ex): void {
						if ($this->dispatcher !== null) {
							$this->dispatcher->dispatch(new Events\ErrorEvent($ex, $this));
						}
					});

					$deferred->resolve($stream);
				},
				function (Throwable $ex) use ($deferred, $timer): void {
					$this->eventLoop->cancelTimer($timer);

					$deferred->reject($ex);
				}
			);

		return $deferred->promise();
	}

	/**
	 * @param RedisProtocol\Model\ModelInterface $message
	 *
	 * @return void
	 */
	private function handleMessage(RedisProtocol\Model\ModelInterface $message): void
	{
		if ($message instanceof RedisProtocol\Model\MultiBulkReply) {
			$array = $message->getValueNative();
			$type = array_shift($array);

			// Pub/Sub messages are to be forwarded and should not be processed as request responses
			if ($type === 'message') {
				if (isset($array[0]) && isset($array[1])) {
					if ($this->dispatcher !== null) {
						$this->dispatcher->dispatch(new Events\MessageReceivedEvent($array[0], $array[1], $this));
					}

					return;

				} else {
					throw new Exceptions\InvalidStateException('Received bulk message in invalid format');
				}
			} elseif ($type === 'pmessage') {
				if (isset($array[0]) && isset($array[1]) && isset($array[2])) {
					if ($this->dispatcher !== null) {
						$this->dispatcher->dispatch(
							new Events\PatternMessageReceivedEvent($array[0], $array[1], $array[2], $this)
						);
					}

					return;
				} else {
					throw new Exceptions\InvalidStateException('Received bulk message in invalid format');
				}
			}
		}

		if ($this->requests === []) {
			throw new UnderflowException('Unexpected reply received, no matching request found');
		}

		$request = array_shift($this->requests);

		if ($message instanceof RedisProtocol\Model\ErrorReply) {
			$request->reject($message);

		} else {
			$request->resolve($message->getValueNative());
		}

		if ($this->closing && $this->requests === []) {
			$this->close();
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function subscribe(string $channel): Promise\PromiseInterface
	{
		$request = new Promise\Deferred();
		$promise = $request->promise();

		if ($this->stream === null || $this->closing) {
			$request->reject(new Exceptions\RuntimeException('Connection closed'));

			return $promise;
		}

		$this->stream->write($this->serializer->getRequestMessage('subscribe', [$channel]));

		$this->requests[] = $request;

		return $promise;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIdentifier(): string
	{
		return $this->identifier;
	}

}
