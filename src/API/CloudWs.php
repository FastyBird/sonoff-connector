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

use DateTimeInterface;
use Evenement;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use GuzzleHttp;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
use Psr\Log;
use Ratchet;
use Ratchet\RFC6455;
use React\EventLoop;
use React\Promise;
use React\Socket;
use RuntimeException;
use stdClass;
use Throwable;
use function array_key_exists;
use function count;
use function http_build_query;
use function intval;
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
final class CloudWs implements Evenement\EventEmitterInterface
{

	use Nette\SmartObject;
	use Evenement\EventEmitterTrait;

	private const SOCKETS_LOGIN_API_ENDPOINT = '/dispatch/app';

	private const LOGIN_MESSAGE_SCHEMA_FILENAME = 'cloud_sockets_login.json';

	private const HANDSHAKE_MESSAGE_SCHEMA_FILENAME = 'cloud_sockets_handshake.json';

	private const DEVICE_STATE_MESSAGE_SCHEMA_FILENAME = 'cloud_sockets_device_state.json';

	private const DEVICE_UPDATE_MESSAGE_SCHEMA_FILENAME = 'cloud_sockets_device_update.json';

	private const DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME = 'cloud_sockets_device_status.json';

	private const USER_ONLINE_ACTION = 'userOnline';

	private const SYSTEM_MESSAGE_ACTION = 'sysmsg';

	private const UPDATE_ACTION = 'update';

	private const QUERY_ACTION = 'query';

	private const WAIT_FOR_REPLY_TIMEOUT = 15.0;

	private bool $connecting = false;

	private bool $connected = false;

	private DateTimeInterface|null $lastConnectAttempt = null;

	private DateTimeInterface|null $disconnected = null;

	private DateTimeInterface|null $lost = null;

	private EventLoop\TimerInterface|null $pingTimer = null;

	/** @var array<string, Entities\API\WsMessage> */
	private array $messages = [];

	private Ratchet\Client\WebSocket|null $connection = null;

	public function __construct(
		private readonly string $identifier,
		private readonly string $accessToken,
		private readonly string $appId,
		private readonly string $apiKey,
		private readonly Types\Region $region,
		private readonly HttpClientFactory $httpClientFactory,
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws RuntimeException
	 */
	public function connect(): Promise\PromiseInterface
	{
		$this->connection = null;
		$this->connecting = true;
		$this->connected = false;

		$this->lastConnectAttempt = $this->dateTimeFactory->getNow();
		$this->lost = null;
		$this->disconnected = null;

		$socketsSettings = $this->login();

		$reactConnector = new Socket\Connector([
			'dns' => '8.8.8.8',
			'timeout' => 10,
			'tls' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
				'check_hostname' => false,
			],
		]);

		$connector = new Ratchet\Client\Connector($this->eventLoop, $reactConnector);

		$deferred = new Promise\Deferred();

		try {
			$connector('wss://' . $socketsSettings->getDomain() . ':' . $socketsSettings->getPort() . '/api/ws')
				->then(function (Ratchet\Client\WebSocket $connection) use ($deferred): void {
					$this->connection = $connection;

					$this->doWsHandshake()
						->then(
							function (Entities\API\SocketsHandshake $response): void {
								$this->connecting = false;
								$this->connected = true;

								$this->lost = null;
								$this->disconnected = null;

								$this->logger->debug(
									'Connected to Sonoff sockets server',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
										'type' => 'cloud-ws',
										'connector' => [
											'identifier' => $this->identifier,
										],
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

								$this->logger->error(
									'Handshake with Sonoff WS failed',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
										'type' => 'cloud-ws',
										'exception' => BootstrapHelpers\Logger::buildException($ex),
										'connector' => [
											'identifier' => $this->identifier,
										],
									],
								);

								$this->emit('error', [$ex]);
							},
						);

					$connection->on('message', function (RFC6455\Messaging\MessageInterface $message): void {
						$this->handleMessage($message->getPayload());
					});

					$connection->on('error', function (Throwable $ex): void {
						$this->logger->error(
							'An error occurred on WS server connection',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
								'type' => 'cloud-ws',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'connector' => [
									'identifier' => $this->identifier,
								],
							],
						);

						$this->lost();

						$this->emit('error', [$ex]);
					});

					$connection->on('close', function ($code = null, $reason = null): void {
						$this->logger->debug(
							'Connection to Sonoff WS server was closed',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
								'type' => 'cloud-ws',
								'connection' => [
									'code' => $code,
									'reason' => $reason,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							],
						);

						$this->disconnect();

						$this->emit('disconnected');
					});

					$this->emit('connected');

					$deferred->resolve();
				})
				->otherwise(function (Throwable $ex) use ($deferred): void {
					$this->logger->error(
						'Connection to Sonoff WS server failed',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
							'type' => 'cloud-ws',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'connector' => [
								'identifier' => $this->identifier,
							],
						],
					);

					$this->connection = null;

					$this->connecting = false;
					$this->connected = false;

					$this->emit('error', [$ex]);

					$deferred->reject($ex);
				});
		} catch (Throwable $ex) {
			$this->connection = null;

			$this->connecting = false;
			$this->connected = false;

			$this->logger->error(
				'Connection to Sonoff WS could not be created',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-ws',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			$this->emit('error', [$ex]);

			$deferred->reject($ex);
		}

		return $deferred->promise();
	}

	public function disconnect(): void
	{
		$this->connection?->close();
		$this->connection = null;

		$this->connecting = false;
		$this->connected = false;

		$this->disconnected = $this->dateTimeFactory->getNow();

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

	public function readStates(string $deviceKey, string $deviceId): void
	{
		$message = new stdClass();
		$message->action = self::QUERY_ACTION;
		$message->apikey = $deviceKey;
		$message->selfApikey = $this->apiKey;
		$message->deviceid = $deviceId;
		$message->userAgent = 'app';
		$message->sequence = strval(intval($this->dateTimeFactory->getNow()->format('Uv')));
		$message->params = [];

		$this->sendRequest($message, $message->action, $message->sequence);
	}

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
			$params->{$group} = new stdClass();
			$params->{$group}->{$index} = new stdClass();
			$params->{$group}->{$index}->{$parameter} = $value;
			$params->{$group}->{$index}->outlet = $index;

		} else {
			$params->{$parameter} = $value;
		}

		$message = new stdClass();
		$message->action = self::UPDATE_ACTION;
		$message->apikey = $apiKey;
		$message->selfApikey = $this->apiKey;
		$message->deviceid = $id;
		$message->userAgent = 'app';
		$message->sequence = strval(intval($this->dateTimeFactory->getNow()->format('Uv')));
		$message->params = $params;

		$this->sendRequest($message, $message->action, $message->sequence, $deferred);

		return $deferred->promise();
	}

	/**
	 * @throws Exceptions\CloudWsCall
	 * @throws RuntimeException
	 */
	private function login(): Entities\API\SocketsLogin
	{
		$response = $this->callRequest(
			'GET',
			self::SOCKETS_LOGIN_API_ENDPOINT,
			[
				'Authorization' => 'Bearer ' . $this->accessToken,
				'Content-Type' => 'application/json',
			],
			[],
			null,
			false,
		);

		if ($response === false) {
			throw new Exceptions\CloudWsCall('Could not authenticate user with cloud sockets');
		}

		try {
			$parsedMessage = $this->schemaValidator->validate(
				$response->getBody()->getContents(),
				$this->getSchema(self::LOGIN_MESSAGE_SCHEMA_FILENAME),
			);
		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
			$response->getBody()->rewind();

			$this->logger->error(
				'Could not decode received response payload',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-ws',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'response' => [
						'body' => $response->getBody()->getContents(),
						'schema' => self::LOGIN_MESSAGE_SCHEMA_FILENAME,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\CloudWsCall('Could not decode received response payload');
		}

		$error = $parsedMessage->offsetGet('error');

		if ($error !== 0) {
			$this->logger->error(
				'Sockets authentication failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-ws',
					'error' => $parsedMessage->offsetGet('reason'),
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\CloudWsCall(
				sprintf('Sockets authentication failed: %s', strval($parsedMessage->offsetGet('reason'))),
			);
		}

		return EntityFactory::build(
			Entities\API\SocketsLogin::class,
			$parsedMessage,
		);
	}

	private function doWsHandshake(): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if ($this->connection === null) {
			return Promise\reject(
				new Exceptions\InvalidState('Connection with cloud server is not established'),
			);
		}

		$timestamp = $this->dateTimeFactory->getNow()->getTimestamp();

		$message = new stdClass();
		$message->action = self::USER_ONLINE_ACTION;
		$message->at = $this->accessToken;
		$message->apikey = $this->apiKey;
		$message->appid = $this->appId;
		$message->nonce = strval(intval($timestamp / 100));
		$message->ts = $timestamp;
		$message->userAgent = 'app';
		$message->sequence = strval(intval($this->dateTimeFactory->getNow()->format('Uv')));
		$message->version = 8;

		$this->sendRequest($message, $message->action, $message->sequence, $deferred);

		return $deferred->promise();
	}

	private function lost(): void
	{
		$this->lost = $this->dateTimeFactory->getNow();

		$this->emit('lost');

		$this->disconnect();
	}

	/**
	 * @throws Exceptions\CloudWsCall
	 * @throws Exceptions\InvalidState
	 */
	private function handleMessage(string $content): void
	{
		try {
			$payload = Utils\Json::decode($content);
		} catch (Utils\JsonException $ex) {
			$this->logger->debug(
				'Received message from WS server could not be parsed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-ws',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			$this->emit('error', [$ex]);

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
					self::DEVICE_STATE_MESSAGE_SCHEMA_FILENAME,
					Entities\API\DeviceState::class,
				);

				if ($entity !== null) {
					$this->emit('message', [$entity]);
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
				new Exceptions\CloudWsCall('An error was received from WS server', $error),
			);

			return;
		}

		if ($message?->getAction() === self::USER_ONLINE_ACTION) {
			$this->parseEntity(
				$content,
				self::HANDSHAKE_MESSAGE_SCHEMA_FILENAME,
				Entities\API\SocketsHandshake::class,
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
					Entities\API\DeviceUpdated::class,
					$message?->getDeferred(),
				);
			} catch (Utils\JsonException) {
				$entity = $this->parseEntity(
					$content,
					self::DEVICE_UPDATE_MESSAGE_SCHEMA_FILENAME,
					Entities\API\DeviceUpdated::class,
					$message?->getDeferred(),
				);
			}

			if ($message?->getDeferred() === null && $entity !== null) {
				$this->emit('message', [$entity]);
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
				self::DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME,
				Entities\API\DeviceStatus::class,
				$this->messages[$sequence]->getDeferred(),
			);

			if ($message?->getDeferred() === null && $entity !== null) {
				$this->emit('message', [$entity]);
			}

			unset($this->messages[$sequence]);
		}
	}

	/**
	 * @template T of Entities\API\Entity
	 *
	 * @param class-string<T> $entityClass
	 *
	 * @return T|null
	 *
	 * @throws Exceptions\CloudWsCall
	 * @throws Exceptions\InvalidState
	 */
	private function parseEntity(
		string $message,
		string $schemaFilename,
		string $entityClass,
		Promise\Deferred|null $deferred = null,
	): Entities\API\Entity|null
	{
		try {
			$parsedMessage = $this->schemaValidator->validate($message, $this->getSchema($schemaFilename));

			$entity = EntityFactory::build($entityClass, $parsedMessage);

			$deferred?->resolve($entity);

			return $entity;
		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
			$this->logger->error(
				'Could not decode received payload',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'ws-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'response' => [
						'body' => $message,
						'schema' => $schemaFilename,
					],
				],
			);

			$deferred?->reject(
				new Exceptions\CloudWsCall('Could not decode received payload'),
			);
		}

		return null;
	}

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

		$this->messages[$seqId] = new Entities\API\WsMessage(
			$payload,
			$action,
			$deferred,
			$timeout,
		);

		try {
			$this->connection?->send(Utils\Json::encode($payload));
		} catch (Utils\JsonException) {
			$deferred?->reject(new Exceptions\CloudWsCallTimeout('Message could not be converted for sending'));
		}
	}

	/**
	 * @param array<string, mixed> $headers
	 * @param array<string, mixed> $params
	 *
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Message\ResponseInterface|false)
	 */
	private function callRequest(
		string $method,
		string $path,
		array $headers = [],
		array $params = [],
		string|null $body = null,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Message\ResponseInterface|false
	{
		$deferred = new Promise\Deferred();

		$requestPath = $this->getSocketsEndpoint()->getValue() . $path;

		$this->logger->debug(sprintf(
			'Request: method = %s url = %s',
			$method,
			$requestPath,
		), [
			'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
			'type' => 'cloud-ws',
			'request' => [
				'method' => $method,
				'url' => $requestPath,
				'headers' => $headers,
				'params' => $params,
				'body' => $body,
			],
			'connector' => [
				'identifier' => $this->identifier,
			],
		]);

		if (count($params) > 0) {
			$requestPath .= '?';
			$requestPath .= http_build_query($params);
		}

		if ($async) {
			try {
				$request = $this->httpClientFactory->createClient()->request(
					$method,
					$requestPath,
					$headers,
					$body ?? '',
				);

				$request
					->then(
						function (Message\ResponseInterface $response) use ($deferred, $method, $path, $headers, $params, $body): void {
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

							$this->logger->debug('Received response', [
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
								'type' => 'cloud-ws',
								'request' => [
									'method' => $method,
									'path' => $path,
									'headers' => $headers,
									'params' => $params,
									'body' => $body,
								],
								'response' => [
									'status_code' => $response->getStatusCode(),
									'body' => $responseBody,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							]);

							$deferred->resolve($response);
						},
						function (Throwable $ex) use ($deferred, $method, $requestPath, $params, $body): void {
							$this->logger->error('Calling api endpoint failed', [
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
								'type' => 'cloud-ws',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'request' => [
									'method' => $method,
									'url' => $requestPath,
									'params' => $params,
									'body' => $body,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							]);

							$deferred->reject($ex);
						},
					);
			} catch (Throwable $ex) {
				return Promise\reject($ex);
			}

			return $deferred->promise();
		} else {
			try {
				$response = $this->httpClientFactory->createClient(false)->request(
					$method,
					$requestPath,
					[
						'headers' => $headers,
						'body' => $body ?? '',
					],
				);

				try {
					$responseBody = $response->getBody()->getContents();

					$response->getBody()->rewind();
				} catch (RuntimeException $ex) {
					throw new Exceptions\CloudWsCall('Could not get content from response body', $ex->getCode(), $ex);
				}

				$this->logger->debug('Received response', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-ws',
					'request' => [
						'method' => $method,
						'path' => $path,
						'headers' => $headers,
						'params' => $params,
						'body' => $body,
					],
					'response' => [
						'status_code' => $response->getStatusCode(),
						'body' => $responseBody,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				]);

				return $response;
			} catch (GuzzleHttp\Exception\GuzzleException | InvalidArgumentException $ex) {
				$this->logger->error('Calling api endpoint failed', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-ws',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'method' => $method,
						'url' => $requestPath,
						'params' => $params,
						'body' => $body,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				]);

				return false;
			} catch (Exceptions\CloudWsCall $ex) {
				$this->logger->error('Received payload is not valid', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-ws',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'method' => $method,
						'url' => $requestPath,
						'params' => $params,
						'body' => $body,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				]);

				return false;
			}
		}
	}

	private function getSocketsEndpoint(): Types\CloudSocketsEndpoint
	{
		if ($this->region->equalsValue(Types\Region::REGION_EUROPE)) {
			return Types\CloudSocketsEndpoint::get(Types\CloudSocketsEndpoint::ENDPOINT_EUROPE);
		}

		if ($this->region->equalsValue(Types\Region::REGION_AMERICA)) {
			return Types\CloudSocketsEndpoint::get(Types\CloudSocketsEndpoint::ENDPOINT_AMERICA);
		}

		if ($this->region->equalsValue(Types\Region::REGION_ASIA)) {
			return Types\CloudSocketsEndpoint::get(Types\CloudSocketsEndpoint::ENDPOINT_ASIA);
		}

		return Types\CloudSocketsEndpoint::get(Types\CloudSocketsEndpoint::ENDPOINT_CHINA);
	}

	/**
	 * @throws Exceptions\CloudWsCall
	 */
	private function getSchema(string $schemaFilename): string
	{
		try {
			$schema = Utils\FileSystem::read(
				Sonoff\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . $schemaFilename,
			);

		} catch (Nette\IOException) {
			throw new Exceptions\CloudWsCall('Validation schema for response could not be loaded');
		}

		return $schema;
	}

}
