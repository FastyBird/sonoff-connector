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

use Clue\React\Multicast;
use Evenement;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use GuzzleHttp;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
use Psr\Log;
use React\Datagram;
use React\Dns;
use React\EventLoop;
use React\Http;
use React\Promise;
use React\Socket\Connector;
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
use function intval;
use function is_array;
use function is_string;
use function preg_match;
use function property_exists;
use function random_bytes;
use function sprintf;
use function strval;

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

	private const CONNECTION_TIMEOUT = 10;

	private const MDNS_ADDRESS = '224.0.0.251';

	private const MDNS_PORT = 5_353;

	private const MATCH_NAME = '/^(?:[a-zA-Z]+)_(?P<id>[0-9A-Za-z]+)._ewelink._tcp.local$/';

	private const MATCH_DOMAIN = '/^(?:[a-zA-Z]+)_(?P<id>[0-9A-Za-z]+).local$/';
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private const MATCH_IP_ADDRESS = '/^((?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])[.]){3}(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/';

	private Dns\Protocol\Parser $parser;

	private Dns\Protocol\BinaryDumper $dumper;

	private Datagram\SocketInterface|null $server = null;

	private Log\LoggerInterface $logger;

	private GuzzleHttp\Client|null $client = null;

	private Http\Browser|null $asyncClient = null;

	public function __construct(
		private readonly string $identifier,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();

		$this->parser = new Dns\Protocol\Parser();
		$this->dumper = new Dns\Protocol\BinaryDumper();
	}

	public function connect(): void
	{
		$factory = new Multicast\Factory($this->eventLoop);

		try {
			$this->server = $factory->createReceiver(self::MDNS_ADDRESS . ':' . self::MDNS_PORT);
		} catch (Throwable $ex) {
			$this->logger->error(
				'Could not create mDNS server',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'lan-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			return;
		}

		$this->server->on('message', function ($message): void {
			try {
				$response = $this->parser->parseMessage($message);

			} catch (InvalidArgumentException) {
				$this->logger->warning(
					'Invalid mDNS question response received',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'lan-api',
						'connector' => [
							'identifier' => $this->identifier,
						],
					],
				);

				return;
			}

			if ($response->tc) {
				$this->logger->warning(
					'The server set the truncated bit although we issued a TCP request',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'lan-api',
						'connector' => [
							'identifier' => $this->identifier,
						],
					],
				);

				return;
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
				$this->emit(
					'message',
					[
						new Entities\API\LanMessage(
							$deviceData['id'],
							$deviceIpAddress,
							$deviceDomain,
							$devicePort,
							$deviceData['type'],
							$deviceData['seq'],
							array_key_exists('iv', $deviceData) ? $deviceData['iv'] : null,
							array_key_exists('encrypt', $deviceData) && boolval($deviceData['encrypt']),
							array_filter(
								[
									$deviceData['data1'],
									array_key_exists('data2', $deviceData) ? $deviceData['data2'] : null,
									array_key_exists('data3', $deviceData) ? $deviceData['data3'] : null,
									array_key_exists('data4', $deviceData) ? $deviceData['data4'] : null,
								],
								static fn ($value) => $value !== null,
							),
						),
					],
				);
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

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : bool)
	 *
	 * @throws Exceptions\LanApiCall
	 */
	public function getDeviceInfo(
		string $id,
		string|null $key,
		string $ipAddress,
		int $port,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|bool
	{
		$deferred = new Promise\Deferred();

		$payload = new stdClass();
		$payload->sequence = strval(intval($this->dateTimeFactory->getNow()->format('Uv')));
		$payload->deviceid = $id;
		$payload->selfApikey = '123';
		$payload->data = new stdClass();

		try {
			if ($key !== null) {
				$iv = random_bytes(16);

				$encrypted = Transformer::encryptMessage(Utils\Json::encode($payload->data), $key, base64_encode($iv));

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

		$result = $this->callRequest(
			'POST',
			'http://' . $ipAddress . ':' . $port . '/zeroconf/info',
			[],
			[],
			$body,
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(static function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$result = Utils\Json::decode($response->getBody()->getContents());
						assert($result instanceof stdClass);

						if (property_exists($result, 'error') && intval($result->error) !== 0) {
							$deferred->reject(
								new Exceptions\LanApiCall('Read device status failed', intval($result->error)),
							);
						} else {
							$deferred->resolve(true);
						}
					} catch (Utils\JsonException $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\LanApiCall('Could send data to cloud server');
		}

		return true;
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : bool)
	 *
	 * @throws Exceptions\LanApiCall
	 */
	public function setDeviceStatus(
		string $id,
		string|null $key,
		string $ipAddress,
		int $port,
		string $parameter,
		string|int|float|bool $value,
		string|null $group = null,
		int|null $index = null,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|bool
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

		$payload = new stdClass();
		$payload->sequence = strval(intval($this->dateTimeFactory->getNow()->format('Uv')));
		$payload->deviceid = $id;
		$payload->selfApikey = '123';
		$payload->data = $params;

		try {
			if ($key !== null) {
				$iv = random_bytes(16);

				$encrypted = Transformer::encryptMessage(Utils\Json::encode($payload->data), $key, base64_encode($iv));

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

		$result = $this->callRequest(
			'POST',
			'http://' . $ipAddress . ':' . $port . '/zeroconf/' . ($group ?? $parameter),
			[
				'Connection' => 'close',
			],
			[],
			$body,
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(static function (Message\ResponseInterface $response) use ($deferred, $id, $params): void {
					try {
						$result = Utils\Json::decode($response->getBody()->getContents());
						assert($result instanceof stdClass);

						if (property_exists($result, 'error') && intval($result->error) !== 0) {
							$deferred->reject(new Exceptions\LanApiCall('Write value to device failed'));
						} else {
							$deferred->resolve(new Entities\API\DeviceUpdated(
								Sonoff\Constants::VALUE_NOT_AVAILABLE,
								$id,
								Utils\ArrayHash::from(
									(array) Utils\Json::decode(Utils\Json::encode($params), Utils\Json::FORCE_ARRAY),
								),
							));
						}
					} catch (Utils\JsonException $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\LanApiCall('Could send data to cloud server');
		}

		return true;
	}

	/**
	 * @param array<string, mixed> $headers
	 * @param array<string, mixed> $params
	 *
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Message\ResponseInterface|false)
	 */
	private function callRequest(
		string $method,
		string $requestPath,
		array $headers = [],
		array $params = [],
		string|null $body = null,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Message\ResponseInterface|false
	{
		$deferred = new Promise\Deferred();

		$this->logger->debug(sprintf(
			'Request: method = %s url = %s',
			$method,
			$requestPath,
		), [
			'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
			'type' => 'lan-api',
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
				$request = $this->getClient()->request(
					$method,
					$requestPath,
					$headers,
					$body ?? '',
				);

				$request
					->then(
						function (Message\ResponseInterface $response) use ($deferred, $method, $requestPath, $headers, $params, $body): void {
							try {
								$responseBody = $response->getBody()->getContents();

								$response->getBody()->rewind();
							} catch (RuntimeException $ex) {
								throw new Exceptions\LanApiCall(
									'Could not get content from response body',
									$ex->getCode(),
									$ex,
								);
							}

							$this->logger->debug('Received response', [
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
								'type' => 'lan-api',
								'request' => [
									'method' => $method,
									'url' => $requestPath,
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
								'type' => 'lan-api',
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
				$response = $this->getClient(false)->request(
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
					throw new Exceptions\LanApiCall('Could not get content from response body', $ex->getCode(), $ex);
				}

				$this->logger->debug('Received response', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'lan-api',
					'request' => [
						'method' => $method,
						'url' => $requestPath,
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
					'type' => 'lan-api',
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
			} catch (Exceptions\LanApiCall $ex) {
				$this->logger->error('Received payload is not valid', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'lan-api',
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

	/**
	 * @return ($async is true ? Http\Browser : GuzzleHttp\Client)
	 *
	 * @throws InvalidArgumentException
	 */
	private function getClient(bool $async = true): GuzzleHttp\Client|Http\Browser
	{
		if ($async) {
			if ($this->asyncClient === null) {
				$this->asyncClient = new Http\Browser(
					new Connector(
						[
							'timeout' => self::CONNECTION_TIMEOUT,
						],
						$this->eventLoop,
					),
					$this->eventLoop,
				);
			}

			return $this->asyncClient;
		} else {
			if ($this->client === null) {
				$this->client = new GuzzleHttp\Client();
			}

			return $this->client;
		}
	}

}
