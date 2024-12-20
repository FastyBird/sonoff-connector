<?php declare(strict_types = 1);

/**
 * CloudWs.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           06.05.23
 */

namespace FastyBird\Connector\Sonoff\API;

use Closure;
use DateTimeInterface;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Services;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Connector\Sonoff\ValueObjects;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Core\Tools\Helpers as ToolsHelpers;
use FastyBird\Core\Tools\Schemas as ToolsSchemas;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use Psr\Http\Message;
use Ratchet;
use Ratchet\RFC6455;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use stdClass;
use Throwable;
use function array_key_exists;
use function assert;
use function count;
use function http_build_query;
use function intval;
use function md5;
use function property_exists;
use function React\Async\async;
use function sprintf;
use function strval;
use const DIRECTORY_SEPARATOR;

/**
 * CoolKit cloud WS interface
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CloudWs
{

	use Nette\SmartObject;

	private const SOCKETS_LOGIN_API_ENDPOINT = '/dispatch/app';

	private const LOGIN_MESSAGE_SCHEMA_FILENAME = 'cloud_sockets_login.json';

	private const HANDSHAKE_MESSAGE_SCHEMA_FILENAME = 'cloud_sockets_handshake.json';

	private const SYSTEM_MESSAGE_SCHEMA_FILENAME = 'cloud_sockets_system.json';

	private const DEVICE_UPDATE_MESSAGE_SCHEMA_FILENAME = 'cloud_sockets_device_update.json';

	private const DEVICE_QUERY_MESSAGE_SCHEMA_FILENAME = 'cloud_sockets_device_query.json';

	private const USER_ONLINE_ACTION = 'userOnline';

	private const SYSTEM_MESSAGE_ACTION = 'sysmsg';

	private const UPDATE_ACTION = 'update';

	private const QUERY_ACTION = 'query';

	private const WAIT_FOR_REPLY_TIMEOUT = 15.0;

	/** @var array<Closure(): void> */
	public array $onConnected = [];

	/** @var array<Closure(): void> */
	public array $onDisconnected = [];

	/** @var array<Closure(): void> */
	public array $onLost = [];

	/** @var array<Closure(Messages\Message $message): void> */
	public array $onMessage = [];

	/** @var array<Closure(Throwable $error): void> */
	public array $onError = [];

	private bool $connecting = false;

	private bool $connected = false;

	/** @var array<string, string> */
	private array $validationSchemas = [];

	private DateTimeInterface|null $lastConnectAttempt = null;

	private DateTimeInterface|null $disconnected = null;

	private DateTimeInterface|null $lost = null;

	private EventLoop\TimerInterface|null $pingTimer = null;

	/** @var array<string, ValueObjects\WsMessage> */
	private array $messages = [];

	private Ratchet\Client\WebSocket|null $connection = null;

	public function __construct(
		private readonly string $accessToken,
		private readonly string $appId,
		private readonly string $apiKey,
		private readonly Types\Region $region,
		private readonly Services\HttpClientFactory $httpClientFactory,
		private readonly Services\WebSocketClientFactory $webSocketClientFactory,
		private readonly Helpers\MessageBuilder $entityHelper,
		private readonly Sonoff\Logger $logger,
		private readonly ToolsSchemas\Validator $schemaValidator,
		private readonly DateTimeFactory\Clock $clock,
		private readonly ObjectMapper\Processing\Processor $objectMapper,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function connect(): Promise\PromiseInterface
	{
		$this->connection = null;
		$this->connecting = true;
		$this->connected = false;

		$this->lastConnectAttempt = $this->clock->getNow();
		$this->lost = null;
		$this->disconnected = null;

		try {
			$socketsSettings = $this->login();

		} catch (Exceptions\CloudWsCall $ex) {
			return Promise\reject($ex);
		} catch (Throwable $ex) {
			return Promise\reject(
				new Exceptions\CloudWsCall('Sockets connector could not be created', $ex->getCode(), $ex),
			);
		}

		$deferred = new Promise\Deferred();

		$this->webSocketClientFactory
			->create('wss://' . $socketsSettings->getDomain() . ':' . $socketsSettings->getPort() . '/api/ws')
			->then(function (Ratchet\Client\WebSocket $connection) use ($deferred): void {
				$this->connection = $connection;

				$this->doWsHandshake()
					->then(
						function (Messages\Response\Sockets\ApplicationHandshake $response): void {
							$this->connecting = false;
							$this->connected = true;

							$this->lost = null;
							$this->disconnected = null;

							$this->logger->debug(
								'Connected to Sonoff sockets server',
								[
									'source' => MetadataTypes\Sources\Connector::SONOFF->value,
									'type' => 'cloud-ws-api',
								],
							);

							if ($response->getConfig()->hasHeartbeat()) {
								$this->pingTimer = $this->eventLoop->addPeriodicTimer(
									$response->getConfig()->getHeartbeatInterval(),
									async(function (): void {
										$this->connection?->send(new RFC6455\Messaging\Frame(
											'ping',
											true,
											RFC6455\Messaging\Frame::OP_PING,
										));
									}),
								);
							}
						},
						function (Throwable $ex): void {
							$this->connection = null;

							$this->connecting = false;
							$this->connected = false;

							Utils\Arrays::invoke(
								$this->onError,
								new Exceptions\InvalidState(
									'Handshake with Sonoff sockets server failed',
									$ex->getCode(),
									$ex,
								),
							);
						},
					);

				$connection->on('message', function (RFC6455\Messaging\MessageInterface $message): void {
					$this->handleMessage($message->getPayload());
				});

				$connection->on('error', function (Throwable $ex): void {
					$this->lost();

					Utils\Arrays::invoke(
						$this->onError,
						new Exceptions\InvalidState(
							'An error occurred on Sonoff sockets server connection',
							$ex->getCode(),
							$ex,
						),
					);
				});

				$connection->on('close', function ($code = null, $reason = null): void {
					$this->logger->debug(
						'Connection to Sonoff sockets server was closed',
						[
							'source' => MetadataTypes\Sources\Connector::SONOFF->value,
							'type' => 'cloud-ws-api',
							'connection' => [
								'code' => $code,
								'reason' => $reason,
							],
						],
					);

					$this->disconnect();

					Utils\Arrays::invoke($this->onDisconnected);
				});

				Utils\Arrays::invoke($this->onConnected);

				$deferred->resolve(true);
			})
			->catch(function (Throwable $ex) use ($deferred): void {
				$this->connection = null;

				$this->connecting = false;
				$this->connected = false;

				Utils\Arrays::invoke($this->onError, $ex);

				$deferred->reject(
					new Exceptions\InvalidState(
						'Connection to Sonoff sockets server failed',
						$ex->getCode(),
						$ex,
					),
				);
			});

		return $deferred->promise();
	}

	public function disconnect(): void
	{
		$this->connection?->close();
		$this->connection = null;

		$this->connecting = false;
		$this->connected = false;

		$this->disconnected = $this->clock->getNow();

		if ($this->pingTimer !== null) {
			$this->eventLoop->cancelTimer($this->pingTimer);

			$this->pingTimer = null;
		}
	}

	public function isConnecting(): bool
	{
		return $this->connecting;
	}

	public function isConnected(): bool
	{
		return $this->connection !== null && !$this->connecting && $this->connected;
	}

	public function getLastConnectAttempt(): DateTimeInterface|null
	{
		return $this->lastConnectAttempt;
	}

	public function getDisconnected(): DateTimeInterface|null
	{
		return $this->disconnected;
	}

	public function getLost(): DateTimeInterface|null
	{
		return $this->lost;
	}

	/**
	 * @return Promise\PromiseInterface<Messages\Response\Sockets\DeviceStateEvent>
	 */
	public function readStates(string $id, string $apiKey): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$message = new stdClass();
		$message->action = self::QUERY_ACTION;
		$message->apikey = $apiKey;
		$message->selfApikey = $this->apiKey;
		$message->deviceid = $id;
		$message->userAgent = 'app';
		$message->sequence = strval(intval($this->clock->getNow()->format('Uv')));
		$message->params = [];

		$this->sendRequest($message, $message->action, $message->sequence, $deferred);

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<Messages\Response\Sockets\DeviceStateEvent>
	 */
	public function writeState(
		string $id,
		string $apiKey,
		string $parameter,
		string|int|float|bool $value,
		string|null $group = null,
		int|null $index = null,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$params = new stdClass();

		if ($group !== null && $index !== null) {
			$item = new stdClass();
			$item->{$parameter} = $value;
			$item->outlet = $index;

			$params->{$group} = [
				$item,
			];

		} else {
			$params->{$parameter} = $value;
		}

		$message = new stdClass();
		$message->action = self::UPDATE_ACTION;
		$message->apikey = $apiKey;
		$message->selfApikey = $this->apiKey;
		$message->deviceid = $id;
		$message->userAgent = 'app';
		$message->sequence = strval(intval($this->clock->getNow()->format('Uv')));
		$message->params = $params;

		$this->sendRequest($message, $message->action, $message->sequence, $deferred);

		return $deferred->promise();
	}

	/**
	 * @throws Exceptions\CloudWsCall
	 * @throws RuntimeException
	 */
	private function login(): Messages\Response\Sockets\ApplicationLogin
	{
		$request = $this->createHttpRequest(
			RequestMethodInterface::METHOD_GET,
			self::SOCKETS_LOGIN_API_ENDPOINT,
			[
				'Authorization' => 'Bearer ' . $this->accessToken,
				'Content-Type' => 'application/json',
			],
		);

		$response = $this->callHttpRequest($request, false);

		$data = $this->validateData($this->getHttpResponseBody($response), self::LOGIN_MESSAGE_SCHEMA_FILENAME);

		$error = $data->offsetGet('error');

		$data = $data->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		if ($error !== 0) {
			throw new Exceptions\CloudWsCall(
				sprintf('User authentication failed: %s', strval($data->offsetGet('msg'))),
			);
		}

		return $this->createEntity(Messages\Response\Sockets\ApplicationLogin::class, $data);
	}

	/**
	 * @return Promise\PromiseInterface<Messages\Response\Sockets\ApplicationHandshake>
	 */
	private function doWsHandshake(): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if ($this->connection === null) {
			return Promise\reject(
				new Exceptions\InvalidState('Connection with Sonoff sockets server is not established'),
			);
		}

		$timestamp = $this->clock->getNow()->getTimestamp();

		$message = new stdClass();
		$message->action = self::USER_ONLINE_ACTION;
		$message->at = $this->accessToken;
		$message->apikey = $this->apiKey;
		$message->appid = $this->appId;
		$message->nonce = strval(intval($timestamp / 100));
		$message->ts = $timestamp;
		$message->userAgent = 'app';
		$message->sequence = strval(intval($this->clock->getNow()->format('Uv')));
		$message->version = 8;

		$this->sendRequest($message, $message->action, $message->sequence, $deferred);

		return $deferred->promise();
	}

	private function lost(): void
	{
		$this->lost = $this->clock->getNow();

		Utils\Arrays::invoke($this->onLost);

		$this->disconnect();
	}

	/**
	 * @throws Exceptions\CloudWsError
	 */
	private function handleMessage(string $content): void
	{
		try {
			$payload = Utils\Json::decode($content);
		} catch (Utils\JsonException $ex) {
			$this->logger->debug(
				'Received message from Sonoff sockets server not be parsed',
				[
					'source' => MetadataTypes\Sources\Connector::SONOFF->value,
					'type' => 'cloud-ws-api',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			Utils\Arrays::invoke($this->onError, $ex);

			return;
		}

		if (!$payload instanceof stdClass) {
			return;
		}

		$error = 0;

		if (property_exists($payload, 'error')) {
			$error = intval($payload->error);
		}

		if (property_exists($payload, 'action')) {
			if ($payload->action === self::SYSTEM_MESSAGE_ACTION) {
				$entity = $this->parseEntity(
					$content,
					self::SYSTEM_MESSAGE_SCHEMA_FILENAME,
					Messages\Response\Sockets\DeviceConnectionStateEvent::class,
				);

				if ($entity !== null) {
					Utils\Arrays::invoke($this->onMessage, $entity);
				}
			}
		}

		$sequence = null;

		if (property_exists($payload, 'sequence')) {
			$sequence = $payload->sequence;
		}

		$message = null;

		if ($sequence !== null && array_key_exists($sequence, $this->messages)) {
			$message = $this->messages[$sequence];
		}

		if ($error !== 0) {
			$message?->getDeferred()?->reject(
				new Exceptions\CloudWsCall('An error was received from Sonoff sockets server', $error),
			);

			return;
		}

		if ($message?->getAction() === self::USER_ONLINE_ACTION) {
			$this->parseEntity(
				$content,
				self::HANDSHAKE_MESSAGE_SCHEMA_FILENAME,
				Messages\Response\Sockets\ApplicationHandshake::class,
				$message->getDeferred(),
			);

			unset($this->messages[$sequence]);

			return;
		}

		if (
			(
				property_exists($payload, 'action')
				&& $payload->action === self::UPDATE_ACTION
			) || $message?->getAction() === self::UPDATE_ACTION
		) {
			if ($message !== null && property_exists($message->getPayload(), 'params')) {
				$payload->params = $message->getPayload()->params;
			}

			try {
				$entity = $this->parseEntity(
					Utils\Json::encode($payload),
					self::DEVICE_UPDATE_MESSAGE_SCHEMA_FILENAME,
					Messages\Response\Sockets\DeviceStateEvent::class,
					$message?->getDeferred(),
				);
			} catch (Utils\JsonException) {
				$entity = $this->parseEntity(
					$content,
					self::DEVICE_UPDATE_MESSAGE_SCHEMA_FILENAME,
					Messages\Response\Sockets\DeviceStateEvent::class,
					$message?->getDeferred(),
				);
			}

			if ($message?->getDeferred() === null && $entity !== null) {
				Utils\Arrays::invoke($this->onMessage, $entity);
			}

			unset($this->messages[$sequence]);

		} elseif (
			(
				property_exists($payload, 'action')
				&& $payload->action === self::QUERY_ACTION
			) || $message?->getAction() === self::QUERY_ACTION
		) {
			$entity = $this->parseEntity(
				$content,
				self::DEVICE_QUERY_MESSAGE_SCHEMA_FILENAME,
				Messages\Response\Sockets\DeviceStateEvent::class,
				$this->messages[$sequence]->getDeferred(),
			);

			if ($message?->getDeferred() === null && $entity !== null) {
				Utils\Arrays::invoke($this->onMessage, $entity);
			}

			unset($this->messages[$sequence]);
		}
	}

	/**
	 * @template T of Messages\Message
	 *
	 * @param class-string<T> $entityClass
	 * @param Promise\Deferred<T>|null $deferred
	 *
	 * @return T|null
	 *
	 * @throws Exceptions\CloudWsError
	 */
	private function parseEntity(
		string $message,
		string $schemaFilename,
		string $entityClass,
		Promise\Deferred|null $deferred = null,
	): Messages\Message|null
	{
		try {
			$entity = $this->createEntity(
				$entityClass,
				$this->validateData($message, $schemaFilename),
			);

			$deferred?->resolve($entity);

			return $entity;
		} catch (Exceptions\CloudWsCall $ex) {
			$deferred?->reject($ex);
		}

		return null;
	}

	/**
	 * @param Promise\Deferred<Messages\Message>|null $deferred
	 */
	private function sendRequest(
		stdClass $payload,
		string $action,
		string $seqId,
		Promise\Deferred|null $deferred = null,
	): void
	{
		$timeout = $this->eventLoop->addTimer(
			self::WAIT_FOR_REPLY_TIMEOUT,
			async(function () use ($deferred, $seqId): void {
				$deferred?->reject(
					new Exceptions\CloudWsCallTimeout('Sending command to cloud through sockets failed'),
				);

				if (array_key_exists($seqId, $this->messages)) {
					if ($this->messages[$seqId]->getTimer() !== null) {
						$this->eventLoop->cancelTimer($this->messages[$seqId]->getTimer());
					}

					unset($this->messages[$seqId]);
				}
			}),
		);

		try {
			$this->messages[$seqId] = $this->objectMapper->process(
				[
					'payload' => $payload,
					'action' => $action,
					'deferred' => $deferred,
					'timeout' => $timeout,
				],
				ValueObjects\WsMessage::class,
			);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			$deferred?->reject(
				new Exceptions\CloudWsCall('Request sign could not be created: ' . $errorPrinter->printError($ex)),
			);

			return;
		}

		try {
			$this->connection?->send(Utils\Json::encode($payload));
		} catch (Utils\JsonException) {
			$deferred?->reject(new Exceptions\CloudWsCallTimeout('Message could not be converted for sending'));
		}
	}

	/**
	 * @throws Exceptions\CloudWsCall
	 */
	private function getHttpResponseBody(
		Message\ResponseInterface $response,
	): string
	{
		try {
			$response->getBody()->rewind();

			return $response->getBody()->getContents();
		} catch (RuntimeException $ex) {
			throw new Exceptions\CloudWsCall(
				'Could not get content from response body',
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @template T of Messages\Message
	 *
	 * @param class-string<T> $entity
	 *
	 * @return T
	 *
	 * @throws Exceptions\CloudWsError
	 */
	private function createEntity(string $entity, Utils\ArrayHash $data): Messages\Message
	{
		try {
			return $this->entityHelper->create(
				$entity,
				(array) Utils\Json::decode(Utils\Json::encode($data), forceArrays: true),
			);
		} catch (Exceptions\Runtime $ex) {
			throw new Exceptions\CloudWsError('Could not map data to entity', $ex->getCode(), $ex);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\CloudWsError(
				'Could not create entity from payload',
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @return ($throw is true ? Utils\ArrayHash : Utils\ArrayHash|false)
	 *
	 * @throws Exceptions\CloudWsCall
	 * @throws Exceptions\CloudWsError
	 */
	private function validateData(
		string $payload,
		string $schemaFilename,
		bool $throw = true,
	): Utils\ArrayHash|bool
	{
		try {
			return $this->schemaValidator->validate(
				$payload,
				$this->getSchema($schemaFilename),
			);
		} catch (ToolsExceptions\Logic | ToolsExceptions\MalformedInput | ToolsExceptions\InvalidData $ex) {
			if ($throw) {
				throw new Exceptions\CloudWsCall(
					'Could not validate received payload',
					$ex->getCode(),
					$ex,
				);
			}

			return false;
		}
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Message\ResponseInterface> : Message\ResponseInterface)
	 *
	 * @throws Exceptions\CloudWsCall
	 */
	private function callHttpRequest(
		Request $request,
		bool $async = true,
	): Promise\PromiseInterface|Message\ResponseInterface
	{
		$deferred = new Promise\Deferred();

		$this->logger->debug(
			sprintf(
				'Request: method = %s url = %s',
				$request->getMethod(),
				$request->getUri(),
			),
			[
				'source' => MetadataTypes\Sources\Connector::SONOFF->value,
				'type' => 'cloud-ws-api',
				'request' => [
					'method' => $request->getMethod(),
					'url' => strval($request->getUri()),
					'headers' => $request->getHeaders(),
					'body' => $request->getContent(),
				],
			],
		);

		if ($async) {
			try {
				$this->httpClientFactory
					->create()
					->send($request)
					->then(
						function (Message\ResponseInterface $response) use ($deferred, $request): void {
							try {
								$responseBody = $response->getBody()->getContents();

								$response->getBody()->rewind();
							} catch (RuntimeException $ex) {
								$deferred->reject(
									new Exceptions\CloudWsCall(
										'Could not get content from response body',
										$ex->getCode(),
										$ex,
									),
								);

								return;
							}

							$this->logger->debug(
								'Received response',
								[
									'source' => MetadataTypes\Sources\Connector::SONOFF->value,
									'type' => 'cloud-ws-api',
									'request' => [
										'method' => $request->getMethod(),
										'url' => strval($request->getUri()),
										'headers' => $request->getHeaders(),
										'body' => $request->getContent(),
									],
									'response' => [
										'code' => $response->getStatusCode(),
										'body' => $responseBody,
									],
								],
							);

							$deferred->resolve($response);
						},
						static function (Throwable $ex) use ($deferred): void {
							$deferred->reject(
								new Exceptions\CloudWsCall(
									'Calling api endpoint failed',
									$ex->getCode(),
									$ex,
								),
							);
						},
					);
			} catch (Throwable $ex) {
				return Promise\reject($ex);
			}

			return $deferred->promise();
		}

		try {
			$response = $this->httpClientFactory
				->create(false)
				->send($request);

			try {
				$responseBody = $response->getBody()->getContents();

				$response->getBody()->rewind();
			} catch (RuntimeException $ex) {
				throw new Exceptions\CloudWsCall(
					'Could not get content from response body',
					$ex->getCode(),
					$ex,
				);
			}

			$this->logger->debug(
				'Received response',
				[
					'source' => MetadataTypes\Sources\Connector::SONOFF->value,
					'type' => 'cloud-ws-api',
					'request' => [
						'method' => $request->getMethod(),
						'url' => strval($request->getUri()),
						'headers' => $request->getHeaders(),
						'body' => $request->getContent(),
					],
					'response' => [
						'code' => $response->getStatusCode(),
						'body' => $responseBody,
					],
				],
			);

			return $response;
		} catch (GuzzleHttp\Exception\GuzzleException | InvalidArgumentException $ex) {
			throw new Exceptions\CloudWsCall(
				'Calling api endpoint failed',
				$ex->getCode(),
				$ex,
			);
		}
	}

	private function getSocketsEndpoint(): Types\CloudSocketsEndpoint
	{
		if ($this->region === Types\Region::EUROPE) {
			return Types\CloudSocketsEndpoint::EUROPE;
		}

		if ($this->region === Types\Region::AMERICA) {
			return Types\CloudSocketsEndpoint::AMERICA;
		}

		if ($this->region === Types\Region::ASIA) {
			return Types\CloudSocketsEndpoint::ASIA;
		}

		return Types\CloudSocketsEndpoint::CHINA;
	}

	/**
	 * @throws Exceptions\CloudWsError
	 */
	private function getSchema(string $schemaFilename): string
	{
		$key = md5($schemaFilename);

		if (!array_key_exists($key, $this->validationSchemas)) {
			try {
				$this->validationSchemas[$key] = Utils\FileSystem::read(
					Sonoff\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . $schemaFilename,
				);

			} catch (Nette\IOException) {
				throw new Exceptions\CloudWsError('Validation schema for response could not be loaded');
			}
		}

		return $this->validationSchemas[$key];
	}

	/**
	 * @param array<string, string|array<string>>|null $headers
	 * @param array<string, mixed> $params
	 *
	 * @throws Exceptions\CloudWsError
	 */
	private function createHttpRequest(
		string $method,
		string $path,
		array|null $headers = null,
		array $params = [],
		string|null $body = null,
	): Request
	{
		$url = $this->getSocketsEndpoint()->value . $path;

		if (count($params) > 0) {
			$url .= '?';
			$url .= http_build_query($params);
		}

		try {
			return new Request($method, $url, $headers, $body);
		} catch (Exceptions\InvalidArgument $ex) {
			throw new Exceptions\CloudWsError('Could not create request instance', $ex->getCode(), $ex);
		}
	}

}
