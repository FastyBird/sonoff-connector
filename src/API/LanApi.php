<?php declare(strict_types = 1);

/**
 * LanApi.php
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

use BadMethodCallException;
use Evenement;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Helpers\Transformer;
use FastyBird\Connector\Sonoff\Services;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
use React\Datagram;
use React\Dns;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use stdClass;
use Throwable;
use function array_filter;
use function array_key_exists;
use function assert;
use function base64_encode;
use function boolval;
use function count;
use function explode;
use function http_build_query;
use function implode;
use function intval;
use function is_array;
use function is_numeric;
use function is_string;
use function preg_match;
use function random_bytes;
use function sprintf;
use function strval;
use const DIRECTORY_SEPARATOR;

/**
 * Local LAN API interface
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class LanApi
{

	use Nette\SmartObject;
	use Evenement\EventEmitterTrait;

	public const DEVICE_PORT = 8_081;

	public const ERROR_INVALID_JSON = 400;

	public const ERROR_UNAUTHORIZED = 401;

	public const ERROR_DEVICE_ID_INVALID = 404;

	public const ERROR_INVALID_PARAMETER = 422;

	private const MDNS_ADDRESS = '224.0.0.251';

	private const MDNS_PORT = 5_353;

	private const MATCH_NAME = '/^(?:[a-zA-Z]+)_(?P<id>[0-9A-Za-z]+)._ewelink._tcp.local$/';

	private const MATCH_DOMAIN = '/^(?:[a-zA-Z]+)_(?P<id>[0-9A-Za-z]+).local$/';
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private const MATCH_IP_ADDRESS = '/^((?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])[.]){3}(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/';

	private const GET_DEVICE_INFO_MESSAGE_SCHEMA_FILENAME = 'lan_api_get_device_info.json';

	private const SET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME = 'lan_api_set_device_state.json';

	/** @var array<string, string> */
	private array $encodeKeys = [];

	private Dns\Protocol\Parser $parser;

	private Dns\Protocol\BinaryDumper $dumper;

	private Datagram\SocketInterface|null $server = null;

	public function __construct(
		private readonly Services\HttpClientFactory $httpClientFactory,
		private readonly Services\MulticastFactory $multicastFactory,
		private readonly Helpers\Entity $entityHelper,
		private readonly Sonoff\Logger $logger,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly MetadataSchemas\Validator $schemaValidator,
	)
	{
		$this->parser = new Dns\Protocol\Parser();
		$this->dumper = new Dns\Protocol\BinaryDumper();
	}

	/**
	 * @throws BadMethodCallException
	 * @throws Exceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function connect(): void
	{
		$this->server = $this->multicastFactory->create(self::MDNS_ADDRESS, self::MDNS_PORT);

		$this->server->on('message', function ($message): void {
			try {
				$response = $this->parser->parseMessage($message);

			} catch (InvalidArgumentException $ex) {
				throw new Exceptions\InvalidState('Invalid mDNS question response received', $ex->getCode(), $ex);
			}

			if ($response->tc) {
				throw new Exceptions\InvalidState('The server set the truncated bit although we issued a TCP request');
			}

			$deviceIpAddress = null;
			$deviceDomain = null;
			$devicePort = self::DEVICE_PORT;
			$deviceData = [];

			foreach ($response->answers as $answer) {
				if (
					$answer->type === Dns\Model\Message::TYPE_A
					&& preg_match(self::MATCH_DOMAIN, $answer->name) === 1
					&& is_string($answer->data)
					&& preg_match(self::MATCH_IP_ADDRESS, $answer->data) === 1
					&& $deviceIpAddress === null
				) {
					$deviceIpAddress = $answer->data;
				}

				if (
					$answer->type === Dns\Model\Message::TYPE_SRV
					&& preg_match(self::MATCH_NAME, $answer->name) === 1
					&& is_array($answer->data)
				) {
					if (array_key_exists('target', $answer->data)) {
						$deviceDomain = $answer->data['target'];
					}

					if (array_key_exists('port', $answer->data)) {
						$devicePort = intval($answer->data['port']);
					}
				}

				if (
					$answer->type === Dns\Model\Message::TYPE_TXT
					&& preg_match(self::MATCH_NAME, $answer->name) === 1
					&& is_array($answer->data)
				) {
					foreach ($answer->data as $dataRow) {
						[$key, $value] = explode('=', $dataRow) + [null, null];

						$deviceData[$key] = $value;
					}
				}
			}

			if (
				$deviceIpAddress !== null
				&& $deviceDomain !== null
				&& $deviceData !== []
				&& array_key_exists('id', $deviceData)
				&& is_string($deviceData['id'])
				&& array_key_exists('type', $deviceData)
				&& is_string($deviceData['type'])
				&& array_key_exists('seq', $deviceData)
				&& is_string($deviceData['seq'])
				&& array_key_exists('data1', $deviceData)
			) {
				$dataEncrypted = array_key_exists('encrypt', $deviceData) && boolval($deviceData['encrypt']);

				$data = array_filter(
					[
						$deviceData['data1'],
						array_key_exists('data2', $deviceData) ? $deviceData['data2'] : null,
						array_key_exists('data3', $deviceData) ? $deviceData['data3'] : null,
						array_key_exists('data4', $deviceData) ? $deviceData['data4'] : null,
					],
					static fn ($value) => $value !== null,
				);

				if (
					($dataEncrypted && array_key_exists($deviceData['id'], $this->encodeKeys))
					|| !$dataEncrypted
				) {
					if ($dataEncrypted) {
						foreach ($data as $index => $row) {
							$data[$index] = Transformer::decryptMessage(
								$row,
								$this->encodeKeys[$deviceData['id']],
								array_key_exists('iv', $deviceData) ? strval($deviceData['iv']) : '',
							);
						}
					}

					$data = Utils\Json::decode(implode($data), Utils\Json::FORCE_ARRAY);
					assert(is_array($data));

					$this->emit(
						'message',
						[
							$this->createEntity(
								Entities\API\Lan\DeviceEvent::class,
								Utils\ArrayHash::from([
									'id' => $deviceData['id'],
									'ip_address' => $deviceIpAddress,
									'domain' => $deviceDomain,
									'port' => $devicePort,
									'type' => $deviceData['type'],
									'seq' => $deviceData['seq'],
									'iv' => array_key_exists('iv', $deviceData) ? $deviceData['iv'] : null,
									'encrypt' => $dataEncrypted,
									'data' => $this->createEntity(
										Entities\API\Lan\DeviceEventData::class,
										Utils\ArrayHash::from($data),
									),
								]),
							),
						],
					);
				} else {
					$this->emit(
						'message',
						[
							$this->createEntity(
								Entities\API\Lan\DeviceEvent::class,
								Utils\ArrayHash::from([
									'id' => $deviceData['id'],
									'ip_address' => $deviceIpAddress,
									'domain' => $deviceDomain,
									'port' => $devicePort,
									'type' => $deviceData['type'],
									'seq' => $deviceData['seq'],
									'iv' => array_key_exists('iv', $deviceData) ? $deviceData['iv'] : null,
									'encrypt' => true,
									'data' => null,
								]),
							),
						],
					);
				}
			}
		});

		$this->eventLoop->futureTick(function (): void {
			$query = new Dns\Query\Query(
				'_ewelink._tcp.local',
				Dns\Model\Message::TYPE_PTR,
				Dns\Model\Message::CLASS_IN,
			);

			$request = $this->dumper->toBinary(Dns\Model\Message::createRequestForQuery($query));

			$this->server?->send($request, self::MDNS_ADDRESS . ':' . self::MDNS_PORT);
		});
	}

	public function disconnect(): void
	{
		$this->server?->close();
	}

	public function registerDeviceKey(string $deviceId, string $deviceKey): void
	{
		$this->encodeKeys[$deviceId] = $deviceKey;
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Lan\DeviceInfo)
	 *
	 * @throws Exceptions\LanApiCall
	 */
	public function getDeviceInfo(
		string $id,
		string $ipAddress,
		int $port,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Lan\DeviceInfo
	{
		$deferred = new Promise\Deferred();

		$payload = new stdClass();
		$payload->sequence = strval(intval($this->dateTimeFactory->getNow()->format('Uv')));
		$payload->deviceid = $id;
		$payload->selfApikey = '123';
		$payload->data = new stdClass();

		try {
			if (array_key_exists($id, $this->encodeKeys)) {
				$iv = random_bytes(16);

				$encrypted = Transformer::encryptMessage(
					Utils\Json::encode($payload->data),
					$this->encodeKeys[$id],
					base64_encode($iv),
				);

				if ($encrypted === false) {
					if ($async) {
						return Promise\reject(new Exceptions\LanApiCall('Could encode data for request'));
					}

					throw new Exceptions\LanApiCall('Could encode data for request');
				}

				$payload->encrypt = true;
				$payload->data = $encrypted;
				$payload->iv = base64_encode($iv);
			}

			$body = Utils\Json::encode($payload);
		} catch (Utils\JsonException) {
			if ($async) {
				return Promise\reject(new Exceptions\LanApiCall('Could prepare data for request'));
			}

			throw new Exceptions\LanApiCall('Could prepare data for request');
		} catch (Throwable) {
			if ($async) {
				return Promise\reject(new Exceptions\LanApiCall('Could encode data for request'));
			}

			throw new Exceptions\LanApiCall('Could encode data for request');
		}

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_POST,
			sprintf('http://%s:%d/zeroconf/info', $ipAddress, $port),
			[],
			[],
			$body,
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetDeviceInfo($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDeviceInfo($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : bool)
	 *
	 * @throws Exceptions\LanApiCall
	 */
	public function setDeviceState(
		string $id,
		string $ipAddress,
		int $port,
		string $parameter,
		string|int|float|bool $value,
		string|null $group = null,
		int|null $outlet = null,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|bool
	{
		$deferred = new Promise\Deferred();

		$params = new stdClass();

		if ($group !== null && $outlet !== null) {
			$item = new stdClass();
			$item->{$parameter} = $value;
			$item->outlet = $outlet;

			$params->{$group} = [
				$item,
			];

		} else {
			$params->{$parameter} = $value;
		}

		$payload = new stdClass();
		$payload->sequence = strval(intval($this->dateTimeFactory->getNow()->format('Uv')));
		$payload->deviceid = $id;
		$payload->selfApikey = '123';
		$payload->data = $params;

		try {
			if (array_key_exists($id, $this->encodeKeys)) {
				$iv = random_bytes(16);

				$encrypted = Transformer::encryptMessage(
					Utils\Json::encode($payload->data),
					$this->encodeKeys[$id],
					base64_encode($iv),
				);

				if ($encrypted === false) {
					if ($async) {
						return Promise\reject(new Exceptions\LanApiCall('Could encode data for request'));
					}

					throw new Exceptions\LanApiCall('Could encode data for request');
				}

				$payload->encrypt = true;
				$payload->data = $encrypted;
				$payload->iv = base64_encode($iv);
			}

			$body = Utils\Json::encode($payload);
		} catch (Utils\JsonException) {
			if ($async) {
				return Promise\reject(new Exceptions\LanApiCall('Could prepare data for request'));
			}

			throw new Exceptions\LanApiCall('Could prepare data for request');
		} catch (Throwable) {
			if ($async) {
				return Promise\reject(new Exceptions\LanApiCall('Could encode data for request'));
			}

			throw new Exceptions\LanApiCall('Could encode data for request');
		}

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_POST,
			sprintf('http://%s:%d/zeroconf/%s', $ipAddress, $port, ($group ?? $parameter)),
			[
				'Connection' => 'close',
			],
			[],
			$body,
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseSetDeviceState($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseSetDeviceState($request, $result);
	}

	/**
	 * @throws Exceptions\LanApiCall
	 */
	private function parseGetDeviceInfo(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\Lan\DeviceInfo
	{
		$body = $this->validateResponseBody($request, $response, self::GET_DEVICE_INFO_MESSAGE_SCHEMA_FILENAME);

		$error = $body->offsetGet('error');
		assert(is_numeric($error));

		$data = $body->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		if ($error !== 0) {
			throw new Exceptions\LanApiCall(
				sprintf('Reading device info failed: %s', strval($body->offsetGet('message'))),
				$request,
				$response,
				intval($error),
			);
		}

		return $this->createEntity(Entities\API\Lan\DeviceInfo::class, $data);
	}

	/**
	 * @throws Exceptions\LanApiCall
	 */
	private function parseSetDeviceState(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): bool
	{
		$body = $this->validateResponseBody($request, $response, self::SET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME);

		$error = $body->offsetGet('error');
		assert(is_numeric($error));

		$data = $body->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		if ($error !== 0) {
			throw new Exceptions\LanApiCall(
				sprintf('Setting device state failed: %s', strval($body->offsetGet('message'))),
				$request,
				$response,
				intval($error),
			);
		}

		return true;
	}

	/**
	 * @template T of Entities\API\Entity
	 *
	 * @param class-string<T> $entity
	 *
	 * @return T
	 *
	 * @throws Exceptions\LanApiCall
	 */
	private function createEntity(string $entity, Utils\ArrayHash $data): Entities\API\Entity
	{
		try {
			return $this->entityHelper->create(
				$entity,
				(array) Utils\Json::decode(Utils\Json::encode($data), Utils\Json::FORCE_ARRAY),
			);
		} catch (Exceptions\Runtime $ex) {
			throw new Exceptions\LanApiCall('Could not map data to entity', null, null, $ex->getCode(), $ex);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\LanApiCall(
				'Could not create entity from response',
				null,
				null,
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @return ($throw is true ? Utils\ArrayHash : Utils\ArrayHash|false)
	 *
	 * @throws Exceptions\LanApiCall
	 */
	private function validateResponseBody(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
		string $schemaFilename,
		bool $throw = true,
	): Utils\ArrayHash|bool
	{
		$body = $this->getResponseBody($request, $response);

		try {
			return $this->schemaValidator->validate(
				$body,
				$this->getSchema($schemaFilename),
			);
		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
			if ($throw) {
				throw new Exceptions\LanApiCall(
					'Could not validate received response payload',
					$request,
					$response,
					$ex->getCode(),
					$ex,
				);
			}

			return false;
		}
	}

	/**
	 * @throws Exceptions\LanApiCall
	 */
	private function getResponseBody(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): string
	{
		try {
			$response->getBody()->rewind();

			return $response->getBody()->getContents();
		} catch (RuntimeException $ex) {
			throw new Exceptions\LanApiCall(
				'Could not get content from response body',
				$request,
				$response,
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Message\ResponseInterface)
	 *
	 * @throws Exceptions\LanApiCall
	 */
	private function callRequest(
		Request $request,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Message\ResponseInterface
	{
		$deferred = new Promise\Deferred();

		$this->logger->debug(sprintf(
			'Request: method = %s url = %s',
			$request->getMethod(),
			$request->getUri(),
		), [
			'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
			'type' => 'lan-api',
			'request' => [
				'method' => $request->getMethod(),
				'url' => strval($request->getUri()),
				'headers' => $request->getHeaders(),
				'body' => $request->getContent(),
			],
		]);

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
									new Exceptions\LanApiCall(
										'Could not get content from response body',
										$request,
										$response,
										$ex->getCode(),
										$ex,
									),
								);

								return;
							}

							$this->logger->debug('Received response', [
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
								'type' => 'lan-api',
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
							]);

							$deferred->resolve($response);
						},
						static function (Throwable $ex) use ($deferred, $request): void {
							$deferred->reject(
								new Exceptions\LanApiCall(
									'Calling api endpoint failed',
									$request,
									null,
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
				throw new Exceptions\LanApiCall(
					'Could not get content from response body',
					$request,
					$response,
					$ex->getCode(),
					$ex,
				);
			}

			$this->logger->debug('Received response', [
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
				'type' => 'lan-api',
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
			]);

			return $response;
		} catch (GuzzleHttp\Exception\GuzzleException | InvalidArgumentException $ex) {
			throw new Exceptions\LanApiCall(
				'Calling api endpoint failed',
				$request,
				null,
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @throws Exceptions\LanApiCall
	 */
	private function getSchema(string $schemaFilename): string
	{
		try {
			$schema = Utils\FileSystem::read(
				Sonoff\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . $schemaFilename,
			);
		} catch (Nette\IOException) {
			throw new Exceptions\LanApiCall('Validation schema for response could not be loaded');
		}

		return $schema;
	}

	/**
	 * @param array<string, string|array<string>>|null $headers
	 * @param array<string, mixed> $params
	 *
	 * @throws Exceptions\LanApiCall
	 */
	private function createRequest(
		string $method,
		string $url,
		array|null $headers = null,
		array $params = [],
		string|null $body = null,
	): Request
	{
		if (count($params) > 0) {
			$url .= '?';
			$url .= http_build_query($params);
		}

		try {
			return new Request($method, $url, $headers, $body);
		} catch (Exceptions\InvalidArgument $ex) {
			throw new Exceptions\LanApiCall('Could not create request instance', null, null, $ex->getCode(), $ex);
		}
	}

}
