<?php declare(strict_types = 1);

/**
 * CloudApi.php
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
use React\Promise;
use RuntimeException;
use stdClass;
use Throwable;
use function assert;
use function base64_encode;
use function count;
use function hash_hmac;
use function http_build_query;
use function in_array;
use function sprintf;
use function str_contains;
use function strval;
use const DIRECTORY_SEPARATOR;

/**
 * CoolKit cloud API interface
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CloudApi implements Evenement\EventEmitterInterface
{

	use Nette\SmartObject;
	use Evenement\EventEmitterTrait;

	private const USER_LOGIN_API_ENDPOINT = '/v2/user/login';

	private const USER_REFRESH_API_ENDPOINT = '/v2/user/refresh';

	private const FAMILY_API_ENDPOINT = '/v2/family';

	private const DEVICES_THING_API_ENDPOINT = '/v2/device/thing';

	private const DEVICES_THING_STATUS_API_ENDPOINT = '/v2/device/thing/status';

	private const USER_LOGIN_MESSAGE_SCHEMA_FILENAME = 'cloud_api_user_login.json';

	private const USER_REFRESH_MESSAGE_SCHEMA_FILENAME = 'cloud_api_user_refresh.json';

	private const FAMILY_MESSAGE_SCHEMA_FILENAME = 'cloud_api_family.json';

	private const DEVICES_THING_MESSAGE_SCHEMA_FILENAME = 'cloud_api_device_thing.json';

	private const DEVICES_THING_STATUS_MESSAGE_SCHEMA_FILENAME = 'cloud_api_device_thing_status.json';

	private const ACCESS_TOKEN_VALID_TIME = 30 * 24 * 60 * 60;

	private string|null $accessToken = null;

	private string|null $refreshToken = null;

	private Entities\API\User|null $user = null;

	private DateTimeInterface|null $tokensAcquired = null;

	private Types\Region $region;

	public function __construct(
		private readonly string $identifier,
		private readonly string $username,
		private readonly string $password,
		private readonly string $appId,
		private readonly string $appSecret,
		private readonly HttpClientFactory $httpClientFactory,
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		Types\Region|null $region = null,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
		$this->region = $region ?? Types\Region::get(Types\Region::REGION_EUROPE);
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 */
	public function connect(): void
	{
		$result = $this->login();

		$this->accessToken = $result->getAccessToken();
		$this->refreshToken = $result->getRefreshToken();
		$this->user = $result->getUser();
		$this->tokensAcquired = $this->dateTimeFactory->getNow();

		$this->region = $result->getRegion();
	}

	public function disconnect(): void
	{
		$this->accessToken = null;
		$this->refreshToken = null;
		$this->user = null;
		$this->tokensAcquired = null;
	}

	public function isConnected(): bool
	{
		return $this->accessToken !== null && $this->refreshToken !== null;
	}

	public function getAccessToken(): string|null
	{
		return $this->accessToken;
	}

	public function getRefreshToken(): string|null
	{
		return $this->refreshToken;
	}

	public function getRegion(): Types\Region
	{
		return $this->region;
	}

	public function getUser(): Entities\API\User|null
	{
		return $this->user;
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Homes)
	 *
	 * @throws Exceptions\CloudApiCall
	 */
	public function getHomes(
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Homes
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$deferred = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			self::FAMILY_API_ENDPOINT,
			[
				'Authorization' => 'Bearer ' . $this->accessToken,
				'Content-Type' => 'application/json',
			],
			[],
			null,
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$responseBody = $response->getBody()->getContents();
					} catch (RuntimeException $ex) {
						$deferred->reject(
							new Exceptions\CloudApiCall(
								'Could not get content from response body',
								$ex->getCode(),
								$ex,
							),
						);

						return;
					}

					$entity = $this->parseHomesResponse(
						$responseBody,
						self::FAMILY_MESSAGE_SCHEMA_FILENAME,
					);

					$deferred->resolve($entity);
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\CloudApiCall('Could load data from cloud server');
		}

		try {
			$responseBody = $result->getBody()->getContents();
		} catch (RuntimeException $ex) {
			throw new Exceptions\CloudApiCall('Could not get content from response body', $ex->getCode(), $ex);
		}

		return $this->parseHomesResponse(
			$responseBody,
			self::FAMILY_MESSAGE_SCHEMA_FILENAME,
		);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\ThingList)
	 *
	 * @throws Exceptions\CloudApiCall
	 */
	public function getHomeThings(
		string $home,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\ThingList
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$deferred = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			self::DEVICES_THING_API_ENDPOINT,
			[
				'Authorization' => 'Bearer ' . $this->accessToken,
				'Content-Type' => 'application/json',
			],
			[
				'num' => 0,
				'familyId' => $home,
			],
			null,
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$responseBody = $response->getBody()->getContents();
					} catch (RuntimeException $ex) {
						$deferred->reject(
							new Exceptions\CloudApiCall(
								'Could not get content from response body',
								$ex->getCode(),
								$ex,
							),
						);

						return;
					}

					$entity = $this->parseThingsResponse(
						$responseBody,
						self::DEVICES_THING_MESSAGE_SCHEMA_FILENAME,
					);

					$deferred->resolve($entity);
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\CloudApiCall('Could load data from cloud server');
		}

		try {
			$responseBody = $result->getBody()->getContents();
		} catch (RuntimeException $ex) {
			throw new Exceptions\CloudApiCall('Could not get content from response body', $ex->getCode(), $ex);
		}

		return $this->parseThingsResponse(
			$responseBody,
			self::DEVICES_THING_MESSAGE_SCHEMA_FILENAME,
		);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : ($itemType is 3 ? Entities\API\Group|false : Entities\API\Device|false))
	 *
	 * @throws Exceptions\CloudApiCall
	 */
	public function getSpecifiedThings(
		string $id,
		int $itemType = 1,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Device|Entities\API\Group|false
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$deferred = new Promise\Deferred();

		$item = new stdClass();
		$item->itemType = $itemType;
		$item->id = $id;

		$payload = new stdClass();
		$payload->thingList = [$item];

		try {
			$result = $this->callRequest(
				'POST',
				self::DEVICES_THING_API_ENDPOINT,
				[
					'Authorization' => 'Bearer ' . $this->accessToken,
					'Content-Type' => 'application/json',
				],
				[],
				Utils\Json::encode($payload),
				$async,
			);
		} catch (Utils\JsonException) {
			if ($async) {
				return Promise\reject(new Exceptions\CloudApiCall('Could prepare data for request'));
			}

			throw new Exceptions\CloudApiCall('Could prepare data for request');
		}

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $id, $itemType): void {
					try {
						$responseBody = $response->getBody()->getContents();
					} catch (RuntimeException $ex) {
						$deferred->reject(
							new Exceptions\CloudApiCall(
								'Could not get content from response body',
								$ex->getCode(),
								$ex,
							),
						);

						return;
					}

					$entity = $this->parseThingResponse(
						$id,
						$itemType,
						$responseBody,
						self::DEVICES_THING_MESSAGE_SCHEMA_FILENAME,
					);

					$deferred->resolve($entity);
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\CloudApiCall('Could load data from cloud server');
		}

		try {
			$responseBody = $result->getBody()->getContents();
		} catch (RuntimeException $ex) {
			throw new Exceptions\CloudApiCall('Could not get content from response body', $ex->getCode(), $ex);
		}

		return $this->parseThingResponse(
			$id,
			$itemType,
			$responseBody,
			self::DEVICES_THING_MESSAGE_SCHEMA_FILENAME,
		);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\DeviceStatus)
	 *
	 * @throws Exceptions\CloudApiCall
	 */
	public function getThingStatus(
		string $id,
		int $itemType = 1,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\DeviceStatus
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$deferred = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			self::DEVICES_THING_STATUS_API_ENDPOINT,
			[
				'Authorization' => 'Bearer ' . $this->accessToken,
				'Content-Type' => 'application/json',
			],
			[
				'type' => $itemType,
				'id' => $id,
			],
			null,
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $id, $itemType): void {
					try {
						$responseBody = $response->getBody()->getContents();
					} catch (RuntimeException $ex) {
						$deferred->reject(
							new Exceptions\CloudApiCall(
								'Could not get content from response body',
								$ex->getCode(),
								$ex,
							),
						);

						return;
					}

					$entity = $this->parseThingStatusResponse(
						$id,
						$itemType,
						$responseBody,
						self::DEVICES_THING_STATUS_MESSAGE_SCHEMA_FILENAME,
					);

					$deferred->resolve($entity);
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\CloudApiCall('Could load data from cloud server');
		}

		try {
			$responseBody = $result->getBody()->getContents();
		} catch (RuntimeException $ex) {
			throw new Exceptions\CloudApiCall('Could not get content from response body', $ex->getCode(), $ex);
		}

		return $this->parseThingStatusResponse(
			$id,
			$itemType,
			$responseBody,
			self::DEVICES_THING_STATUS_MESSAGE_SCHEMA_FILENAME,
		);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : bool)
	 *
	 * @throws Exceptions\CloudApiCall
	 */
	public function setThingStatus(
		string $id,
		string $parameter,
		string|int|float|bool $value,
		string|null $group = null,
		int|null $index = null,
		int $itemType = 1,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|bool
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

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
		$payload->type = $itemType;
		$payload->id = $id;
		$payload->params = $params;

		try {
			$result = $this->callRequest(
				'POST',
				self::DEVICES_THING_STATUS_API_ENDPOINT,
				[
					'Authorization' => 'Bearer ' . $this->accessToken,
					'Content-Type' => 'application/json',
				],
				[],
				Utils\Json::encode($payload),
				$async,
			);
		} catch (Utils\JsonException) {
			if ($async) {
				return Promise\reject(new Exceptions\CloudApiCall('Could prepare data for request'));
			}

			throw new Exceptions\CloudApiCall('Could prepare data for request');
		}

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\CloudApiCall('Could send data to cloud server');
		}

		return true;
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 */
	private function login(bool $redirect = false): Entities\API\UserLogin
	{
		$payload = new stdClass();
		$payload->password = $this->password;
		$payload->countryCode = '+86';

		if (str_contains($this->username, '@')) {
			$payload->email = $this->username;
		} elseif (Utils\Strings::startsWith($this->username, '+')) {
			$payload->phoneNumber = $this->username;
		} else {
			$payload->phoneNumber = '+' . $this->username;
		}

		try {
			$data = Utils\Json::encode($payload);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\CloudApiCall(
				'Could not create request data for user authentication',
				$ex->getCode(),
				$ex,
			);
		}

		$hexDig = base64_encode(hash_hmac('sha256', $data, $this->appSecret, true));

		$response = $this->callRequest(
			'POST',
			self::USER_LOGIN_API_ENDPOINT,
			[
				'Authorization' => 'Sign ' . $hexDig,
				'Content-Type' => 'application/json',
				'X-CK-Appid' => $this->appId,
			],
			[],
			$data,
			false,
		);

		if ($response === false) {
			throw new Exceptions\CloudApiCall('Could load data from cloud server');
		}

		try {
			$responseBody = $response->getBody()->getContents();
		} catch (RuntimeException $ex) {
			throw new Exceptions\CloudApiCall('Could not get content from response body', $ex->getCode(), $ex);
		}

		try {
			$parsedMessage = $this->schemaValidator->validate(
				$responseBody,
				$this->getSchema(self::USER_LOGIN_MESSAGE_SCHEMA_FILENAME),
			);
		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
			$this->logger->error(
				'Could not decode received response payload',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'response' => [
						'body' => $responseBody,
						'schema' => self::USER_LOGIN_MESSAGE_SCHEMA_FILENAME,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\CloudApiCall('Could not decode received response payload');
		}

		$error = $parsedMessage->offsetGet('error');

		$data = $parsedMessage->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		if ($error === 10_004) {
			if ($redirect) {
				throw new Exceptions\CloudApiCall('Could not login to user region');
			}

			$this->region = Types\Region::get($data->offsetGet('region'));

			return $this->login(true);
		}

		if ($error !== 0) {
			$this->logger->error(
				'User authentication failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-api',
					'error' => $parsedMessage->offsetGet('msg'),
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\CloudApiCall(
				sprintf('User authentication failed: %s', strval($parsedMessage->offsetGet('msg'))),
			);
		}

		try {
			return EntityFactory::build(Entities\API\UserLogin::class, $data);
		} catch (Exceptions\InvalidState $ex) {
			throw new Exceptions\CloudApiCall('Could not create entity from response', $ex->getCode(), $ex);
		}
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 */
	private function refreshToken(): Entities\API\UserRefresh
	{
		$payload = new stdClass();
		$payload->rt = $this->refreshToken;

		try {
			$data = Utils\Json::encode($payload);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\CloudApiCall(
				'Could not create request data for user token refresh',
				$ex->getCode(),
				$ex,
			);
		}

		$response = $this->callRequest(
			'POST',
			self::USER_REFRESH_API_ENDPOINT,
			[
				'Authorization' => 'Bearer ' . $this->accessToken,
				'Content-Type' => 'application/json',
				'X-CK-Appid' => $this->appId,
			],
			[],
			$data,
			false,
		);

		if ($response === false) {
			throw new Exceptions\CloudApiCall('Could load data from cloud server');
		}

		try {
			$responseBody = $response->getBody()->getContents();
		} catch (RuntimeException $ex) {
			throw new Exceptions\CloudApiCall('Could not get content from response body', $ex->getCode(), $ex);
		}

		try {
			$parsedMessage = $this->schemaValidator->validate(
				$responseBody,
				$this->getSchema(self::USER_REFRESH_MESSAGE_SCHEMA_FILENAME),
			);
		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
			$this->logger->error(
				'Could not decode received response payload',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'response' => [
						'body' => $responseBody,
						'schema' => self::USER_REFRESH_MESSAGE_SCHEMA_FILENAME,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\CloudApiCall('Could not decode received response payload');
		}

		$error = $parsedMessage->offsetGet('error');

		$data = $parsedMessage->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		if ($error !== 0) {
			$this->logger->error(
				'User refresh failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-api',
					'error' => $parsedMessage->offsetGet('msg'),
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\CloudApiCall(
				sprintf('User authentication failed: %s', strval($parsedMessage->offsetGet('msg'))),
			);
		}

		try {
			return EntityFactory::build(Entities\API\UserRefresh::class, $data);
		} catch (Exceptions\InvalidState $ex) {
			throw new Exceptions\CloudApiCall('Could not create entity from response', $ex->getCode(), $ex);
		}
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 */
	private function parseHomesResponse(
		string $payload,
		string $schemaFilename,
	): Entities\API\Homes
	{
		try {
			$parsedMessage = $this->schemaValidator->validate(
				$payload,
				$this->getSchema($schemaFilename),
			);
		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
			$this->logger->error(
				'Could not decode received response payload',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'response' => [
						'body' => $payload,
						'schema' => $schemaFilename,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\CloudApiCall('Could not decode received response payload', $ex->getCode(), $ex);
		}

		$error = $parsedMessage->offsetGet('error');

		if ($error !== 0) {
			$this->logger->error(
				'Load user homes failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-api',
					'error' => $parsedMessage->offsetGet('msg'),
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\CloudApiCall(
				sprintf('Load user homes failed: %s', strval($parsedMessage->offsetGet('msg'))),
			);
		}

		$data = $parsedMessage->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		try {
			return EntityFactory::build(Entities\API\Homes::class, $data);
		} catch (Exceptions\InvalidState $ex) {
			throw new Exceptions\CloudApiCall('Could not create entity from response', $ex->getCode(), $ex);
		}
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 */
	private function parseThingsResponse(
		string $payload,
		string $schemaFilename,
	): Entities\API\ThingList
	{
		try {
			$parsedMessage = $this->schemaValidator->validate(
				$payload,
				$this->getSchema($schemaFilename),
			);
		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
			$this->logger->error(
				'Could not decode received response payload',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'response' => [
						'body' => $payload,
						'schema' => $schemaFilename,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\CloudApiCall('Could not decode received response payload', $ex->getCode(), $ex);
		}

		$error = $parsedMessage->offsetGet('error');

		if ($error !== 0) {
			$this->logger->error(
				'Load home things failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-api',
					'error' => $parsedMessage->offsetGet('msg'),
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\CloudApiCall(
				sprintf('Load home things failed: %s', strval($parsedMessage->offsetGet('msg'))),
			);
		}

		$data = $parsedMessage->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		$thingList = $data->offsetGet('thingList');
		assert($thingList instanceof Utils\ArrayHash);

		$devices = [];
		$groups = [];

		foreach ($thingList as $item) {
			assert($item instanceof Utils\ArrayHash);

			$data = $item->offsetGet('itemData');
			assert($data instanceof Utils\ArrayHash);

			if (in_array($item->offsetGet('itemType'), [1, 2], true)) {
				try {
					$devices[] = EntityFactory::build(
						Entities\API\Device::class,
						$data,
					);
				} catch (Exceptions\InvalidState $ex) {
					throw new Exceptions\CloudApiCall('Could not create entity from response', $ex->getCode(), $ex);
				}
			} elseif ($item->offsetGet('itemType') === 3) {
				try {
					$groups[] = EntityFactory::build(
						Entities\API\Group::class,
						$data,
					);
				} catch (Exceptions\InvalidState $ex) {
					throw new Exceptions\CloudApiCall('Could not create entity from response', $ex->getCode(), $ex);
				}
			}
		}

		return new Entities\API\ThingList($devices, $groups);
	}

	/**
	 * @return ($itemType is 3 ? Entities\API\Group|false : Entities\API\Device|false)
	 *
	 * @throws Exceptions\CloudApiCall
	 */
	private function parseThingResponse(
		string $id,
		int $itemType,
		string $payload,
		string $schemaFilename,
	): Entities\API\Device|Entities\API\Group|false
	{
		try {
			$parsedMessage = $this->schemaValidator->validate(
				$payload,
				$this->getSchema($schemaFilename),
			);
		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
			$this->logger->error(
				'Could not decode received response payload',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'response' => [
						'body' => $payload,
						'schema' => $schemaFilename,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\CloudApiCall('Could not decode received response payload', $ex->getCode(), $ex);
		}

		$error = $parsedMessage->offsetGet('error');

		if ($error !== 0) {
			$this->logger->error(
				'Load specified things failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-api',
					'error' => $parsedMessage->offsetGet('msg'),
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\CloudApiCall(
				sprintf('Load specified things failed: %s', strval($parsedMessage->offsetGet('msg'))),
			);
		}

		$data = $parsedMessage->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		$thingList = $data->offsetGet('thingList');
		assert($thingList instanceof Utils\ArrayHash);

		foreach ($thingList as $item) {
			assert($item instanceof Utils\ArrayHash);

			$data = $item->offsetGet('itemData');
			assert($data instanceof Utils\ArrayHash);

			if (
				in_array($item->offsetGet('itemType'), [1, 2], true)
				&& $item->offsetGet('itemType') === $itemType
				&& $data->offsetExists('deviceid')
				&& $data->offsetGet('deviceid') === $id
			) {
				try {
					return EntityFactory::build(
						Entities\API\Device::class,
						$data,
					);
				} catch (Exceptions\InvalidState $ex) {
					throw new Exceptions\CloudApiCall('Could not create entity from response', $ex->getCode(), $ex);
				}
			} elseif (
				$item->offsetGet('itemType') === 3
				&& $item->offsetGet('itemType') === $itemType
				&& $data->offsetExists('id')
				&& $data->offsetGet('id') === $id
			) {
				try {
					return EntityFactory::build(
						Entities\API\Group::class,
						$data,
					);
				} catch (Exceptions\InvalidState $ex) {
					throw new Exceptions\CloudApiCall('Could not create entity from response', $ex->getCode(), $ex);
				}
			}
		}

		return false;
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 */
	private function parseThingStatusResponse(
		string $id,
		int $itemType,
		string $payload,
		string $schemaFilename,
	): Entities\API\DeviceStatus
	{
		try {
			$parsedMessage = $this->schemaValidator->validate(
				$payload,
				$this->getSchema($schemaFilename),
			);
		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
			$this->logger->error(
				'Could not decode received response payload',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'response' => [
						'body' => $payload,
						'schema' => $schemaFilename,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\CloudApiCall('Could not decode received response payload', $ex->getCode(), $ex);
		}

		$error = $parsedMessage->offsetGet('error');

		if ($error !== 0) {
			$this->logger->error(
				'Load thing status failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-api',
					'error' => $parsedMessage->offsetGet('msg'),
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\CloudApiCall(
				sprintf('Load thing status failed: %s', strval($parsedMessage->offsetGet('msg'))),
			);
		}

		$data = $parsedMessage->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		$params = $data->offsetGet('params');
		assert($params instanceof Utils\ArrayHash);

		return new Entities\API\DeviceStatus(null, $id, $params);
	}

	/**
	 * @param array<string, mixed> $headers
	 * @param array<string, mixed> $params
	 *
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Message\ResponseInterface|false)
	 *
	 * @throws Exceptions\CloudApiCall
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

		if (
			$path !== self::USER_REFRESH_API_ENDPOINT
			&& $this->tokensAcquired?->diff($this->dateTimeFactory->getNow())->s >= self::ACCESS_TOKEN_VALID_TIME
			&& $this->refreshToken !== null
		) {
			$this->refreshToken();
		}

		$requestPath = $this->getApiEndpoint()->getValue() . $path;

		$this->logger->debug(sprintf(
			'Request: method = %s url = %s',
			$method,
			$requestPath,
		), [
			'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
			'type' => 'cloud-api',
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
						function (Message\ResponseInterface $response) use ($deferred, $method, $requestPath, $headers, $params, $body): void {
							try {
								$responseBody = $response->getBody()->getContents();

								$response->getBody()->rewind();
							} catch (RuntimeException $ex) {
								throw new Exceptions\CloudApiCall(
									'Could not get content from response body',
									$ex->getCode(),
									$ex,
								);
							}

							$this->logger->debug('Received response', [
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
								'type' => 'cloud-api',
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
								'type' => 'cloud-api',
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
					throw new Exceptions\CloudApiCall('Could not get content from response body', $ex->getCode(), $ex);
				}

				$this->logger->debug('Received response', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-api',
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
					'type' => 'cloud-api',
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
			} catch (Exceptions\CloudApiCall $ex) {
				$this->logger->error('Received payload is not valid', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-api',
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

	private function getApiEndpoint(): Types\CloudApiEndpoint
	{
		if ($this->region->equalsValue(Types\Region::REGION_EUROPE)) {
			return Types\CloudApiEndpoint::get(Types\CloudApiEndpoint::ENDPOINT_EUROPE);
		}

		if ($this->region->equalsValue(Types\Region::REGION_AMERICA)) {
			return Types\CloudApiEndpoint::get(Types\CloudApiEndpoint::ENDPOINT_AMERICA);
		}

		if ($this->region->equalsValue(Types\Region::REGION_ASIA)) {
			return Types\CloudApiEndpoint::get(Types\CloudApiEndpoint::ENDPOINT_ASIA);
		}

		return Types\CloudApiEndpoint::get(Types\CloudApiEndpoint::ENDPOINT_CHINA);
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 */
	private function getSchema(string $schemaFilename): string
	{
		try {
			$schema = Utils\FileSystem::read(
				Sonoff\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . $schemaFilename,
			);

		} catch (Nette\IOException) {
			throw new Exceptions\CloudApiCall('Validation schema for response could not be loaded');
		}

		return $schema;
	}

}
