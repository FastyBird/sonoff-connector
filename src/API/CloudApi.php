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
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Services;
use FastyBird\Connector\Sonoff\Types;
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
use React\Promise;
use RuntimeException;
use stdClass;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function assert;
use function base64_encode;
use function count;
use function hash_hmac;
use function http_build_query;
use function in_array;
use function md5;
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
final class CloudApi
{

	use Nette\SmartObject;

	private const USER_LOGIN_API_ENDPOINT = '/v2/user/login';

	private const USER_REFRESH_API_ENDPOINT = '/v2/user/refresh';

	private const FAMILY_API_ENDPOINT = '/v2/family';

	private const FAMILY_THINGS_API_ENDPOINT = '/v2/device/thing';

	private const THING_STATE_API_ENDPOINT = '/v2/device/thing/status';

	private const ADD_THIRD_PARTY_DEVICE_API_ENDPOINT = '/v2/device/inherit/add-partner-device';

	private const USER_LOGIN_MESSAGE_SCHEMA_FILENAME = 'cloud_api_user_login.json';

	private const USER_REFRESH_MESSAGE_SCHEMA_FILENAME = 'cloud_api_user_refresh.json';

	private const GET_FAMILY_MESSAGE_SCHEMA_FILENAME = 'cloud_api_get_family.json';

	private const GET_FAMILY_THINGS_MESSAGE_SCHEMA_FILENAME = 'cloud_api_get_family_things.json';

	private const GET_THING_STATE_MESSAGE_SCHEMA_FILENAME = 'cloud_api_get_thing_state.json';

	private const SET_THING_STATE_MESSAGE_SCHEMA_FILENAME = 'cloud_api_set_thing_state.json';

	private const ADD_THIRD_PARTY_DEVICE_MESSAGE_SCHEMA_FILENAME = 'cloud_api_add_third_party_device.json';

	private const API_ERROR = 'cloud_api_error.json';

	private const ACCESS_TOKEN_VALID_TIME = 30 * 24 * 60 * 60;

	private string|null $accessToken = null;

	private string|null $refreshToken = null;

	/** @var array<string, string> */
	private array $validationSchemas = [];

	private Messages\Response\Cloud\User|null $user = null;

	private DateTimeInterface|null $tokensAcquired = null;

	private Types\Region $region;

	public function __construct(
		private readonly string $username,
		private readonly string $password,
		private readonly string $appId,
		private readonly string $appSecret,
		private readonly Services\HttpClientFactory $httpClientFactory,
		private readonly Helpers\MessageBuilder $entityHelper,
		private readonly Sonoff\Logger $logger,
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		Types\Region|null $region = null,
	)
	{
		$this->region = $region ?? Types\Region::EUROPE;
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 * @throws TypeError
	 * @throws ValueError
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

	public function getUser(): Messages\Response\Cloud\User|null
	{
		return $this->user;
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\Cloud\Family> : Messages\Response\Cloud\Family)
	 *
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getFamily(
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\Cloud\Family
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$deferred = new Promise\Deferred();

		try {
			$request = $this->createRequest(
				RequestMethodInterface::METHOD_GET,
				self::FAMILY_API_ENDPOINT,
				[
					'Authorization' => 'Bearer ' . $this->accessToken,
					'Content-Type' => 'application/json',
				],
			);
		} catch (Exceptions\CloudApiError $ex) {
			if ($async) {
				return Promise\reject($ex);
			}

			throw $ex;
		}

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetFamily($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetFamily($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\Cloud\Things> : Messages\Response\Cloud\Things)
	 *
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getFamilyThings(
		string $familyId,
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\Cloud\Things
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$deferred = new Promise\Deferred();

		try {
			$request = $this->createRequest(
				RequestMethodInterface::METHOD_GET,
				self::FAMILY_THINGS_API_ENDPOINT,
				[
					'Authorization' => 'Bearer ' . $this->accessToken,
					'Content-Type' => 'application/json',
				],
				[
					'num' => 0,
					'familyId' => $familyId,
				],
			);
		} catch (Exceptions\CloudApiError $ex) {
			if ($async) {
				return Promise\reject($ex);
			}

			throw $ex;
		}

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetFamilyThings($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetFamilyThings($request, $result);
	}

	/**
	 * @return ($async is true ? ($itemType is 3 ? Promise\PromiseInterface<Messages\Response\Cloud\Group> : Promise\PromiseInterface<Messages\Response\Cloud\Device>) : ($itemType is 3 ? Messages\Response\Cloud\Group : Messages\Response\Cloud\Device))
	 *
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getThing(
		string $id,
		int $itemType = 1,
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\Cloud\Device|Messages\Response\Cloud\Group
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
			$body = Utils\Json::encode($payload);
		} catch (Utils\JsonException $ex) {
			if ($async) {
				return Promise\reject(new Exceptions\CloudApiCall(
					'Message body could not be encoded',
					null,
					null,
					$ex->getCode(),
					$ex,
				));
			}

			throw new Exceptions\CloudApiCall(
				'Message body could not be encoded',
				null,
				null,
				$ex->getCode(),
				$ex,
			);
		}

		try {
			$request = $this->createRequest(
				RequestMethodInterface::METHOD_POST,
				self::FAMILY_THINGS_API_ENDPOINT,
				[
					'Authorization' => 'Bearer ' . $this->accessToken,
					'Content-Type' => 'application/json',
				],
				[],
				$body,
			);
		} catch (Exceptions\CloudApiError $ex) {
			if ($async) {
				return Promise\reject($ex);
			}

			throw $ex;
		}

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetThing($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetThing($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\Cloud\DeviceState> : Messages\Response\Cloud\DeviceState)
	 *
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getThingState(
		string $id,
		int $itemType = 1,
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\Cloud\DeviceState
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$deferred = new Promise\Deferred();

		try {
			$request = $this->createRequest(
				RequestMethodInterface::METHOD_GET,
				self::THING_STATE_API_ENDPOINT,
				[
					'Authorization' => 'Bearer ' . $this->accessToken,
					'Content-Type' => 'application/json',
				],
				[
					'type' => $itemType,
					'id' => $id,
				],
			);
		} catch (Exceptions\CloudApiError $ex) {
			if ($async) {
				return Promise\reject($ex);
			}

			throw $ex;
		}

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request, $id): void {
					try {
						$deferred->resolve($this->parseGetThingState($id, $request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetThingState($id, $request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<bool> : bool)
	 *
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function setThingState(
		string $id,
		string $parameter,
		string|int|float|bool $value,
		Types\ChannelGroup|null $group = null,
		int|null $outlet = null,
		int $itemType = 1,
		bool $async = true,
	): Promise\PromiseInterface|bool
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$deferred = new Promise\Deferred();

		$params = new stdClass();

		if ($group !== null && $outlet !== null) {
			$item = new stdClass();
			$item->{$parameter} = $value;
			$item->outlet = $outlet;

			$params->{$group->value} = [
				$item,
			];

		} else {
			$params->{$parameter} = $value;
		}

		$payload = new stdClass();
		$payload->type = $itemType;
		$payload->id = $id;
		$payload->params = $params;

		try {
			$body = Utils\Json::encode($payload);
		} catch (Utils\JsonException $ex) {
			if ($async) {
				return Promise\reject(new Exceptions\CloudApiCall(
					'Message body could not be encoded',
					null,
					null,
					$ex->getCode(),
					$ex,
				));
			}

			throw new Exceptions\CloudApiCall(
				'Message body could not be encoded',
				null,
				null,
				$ex->getCode(),
				$ex,
			);
		}

		try {
			$request = $this->createRequest(
				RequestMethodInterface::METHOD_POST,
				self::THING_STATE_API_ENDPOINT,
				[
					'Authorization' => 'Bearer ' . $this->accessToken,
					'Content-Type' => 'application/json',
				],
				[],
				$body,
			);
		} catch (Exceptions\CloudApiError $ex) {
			if ($async) {
				return Promise\reject($ex);
			}

			throw $ex;
		}

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseSetThingState($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseSetThingState($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\Cloud\ThirdPartyDevice> : Messages\Response\Cloud\ThirdPartyDevice)
	 *
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function addThirdPartyDevice(
		string $id,
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\Cloud\ThirdPartyDevice
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$deferred = new Promise\Deferred();

		$device = new stdClass();
		$device->uniqueID = $id;

		$payload = new stdClass();
		$payload->type = 23;
		$payload->partnerDevice = [
			$device,
		];

		try {
			$body = Utils\Json::encode($payload);
		} catch (Utils\JsonException $ex) {
			if ($async) {
				return Promise\reject(new Exceptions\CloudApiCall(
					'Message body could not be encoded',
					null,
					null,
					$ex->getCode(),
					$ex,
				));
			}

			throw new Exceptions\CloudApiCall(
				'Message body could not be encoded',
				null,
				null,
				$ex->getCode(),
				$ex,
			);
		}

		try {
			$request = $this->createRequest(
				RequestMethodInterface::METHOD_POST,
				self::ADD_THIRD_PARTY_DEVICE_API_ENDPOINT,
				[
					'Authorization' => 'Bearer ' . $this->accessToken,
					'Content-Type' => 'application/json',
					'X-CK-Appid' => $this->appId,
				],
				[],
				$body,
			);
		} catch (Exceptions\CloudApiError $ex) {
			if ($async) {
				return Promise\reject($ex);
			}

			throw $ex;
		}

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseAddThirdPartyDevice($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseAddThirdPartyDevice($request, $result);
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function login(bool $redirect = false): Messages\Response\Cloud\UserLogin
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
			$body = Utils\Json::encode($payload);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\CloudApiCall(
				'Could not create request data for user authentication',
				null,
				null,
				$ex->getCode(),
				$ex,
			);
		}

		$hexDig = base64_encode(hash_hmac('sha256', $body, $this->appSecret, true));

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_POST,
			self::USER_LOGIN_API_ENDPOINT,
			[
				'Authorization' => 'Sign ' . $hexDig,
				'Content-Type' => 'application/json',
				'X-CK-Appid' => $this->appId,
			],
			[],
			$body,
		);

		$response = $this->callRequest($request, false);

		$data = $this->validateResponseBody($request, $response, self::USER_LOGIN_MESSAGE_SCHEMA_FILENAME);

		$error = $data->offsetGet('error');

		$data = $data->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		if ($error === 10_004) {
			if ($redirect) {
				throw new Exceptions\CloudApiCall('Could not login to user region', $request, $response);
			}

			$this->region = Types\Region::from(strval($data->offsetGet('region')));

			return $this->login(true);
		}

		if ($error !== 0) {
			throw new Exceptions\CloudApiCall(
				sprintf('User authentication failed: %s', strval($data->offsetGet('msg'))),
				$request,
				$response,
			);
		}

		return $this->createEntity(Messages\Response\Cloud\UserLogin::class, $data);
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 */
	private function refreshToken(): Messages\Response\Cloud\UserRefresh
	{
		$payload = new stdClass();
		$payload->rt = $this->refreshToken;

		try {
			$body = Utils\Json::encode($payload);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\CloudApiCall(
				'Could not create request data for user token refresh',
				null,
				null,
				$ex->getCode(),
				$ex,
			);
		}

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_POST,
			self::USER_REFRESH_API_ENDPOINT,
			[
				'Authorization' => 'Bearer ' . $this->accessToken,
				'Content-Type' => 'application/json',
				'X-CK-Appid' => $this->appId,
			],
			[],
			$body,
		);

		$response = $this->callRequest($request, false);

		$data = $this->validateResponseBody($request, $response, self::USER_REFRESH_MESSAGE_SCHEMA_FILENAME);

		$error = $data->offsetGet('error');

		$data = $data->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		if ($error !== 0) {
			throw new Exceptions\CloudApiCall(
				sprintf('Refreshing user access token failed: %s', strval($data->offsetGet('msg'))),
				$request,
				$response,
			);
		}

		return $this->createEntity(
			Messages\Response\Cloud\UserRefresh::class,
			$data,
		);
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 */
	private function parseGetFamily(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Messages\Response\Cloud\Family
	{
		$body = $this->validateResponseBody($request, $response, self::GET_FAMILY_MESSAGE_SCHEMA_FILENAME);

		$error = $body->offsetGet('error');

		if ($error !== 0) {
			throw new Exceptions\CloudApiCall(
				sprintf('Load family detail failed: %s', strval($body->offsetGet('msg'))),
				$request,
				$response,
			);
		}

		$data = $body->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		return $this->createEntity(Messages\Response\Cloud\Family::class, $data);
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 */
	private function parseGetFamilyThings(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Messages\Response\Cloud\Things
	{
		$body = $this->validateResponseBody($request, $response, self::GET_FAMILY_THINGS_MESSAGE_SCHEMA_FILENAME);

		$error = $body->offsetGet('error');

		if ($error !== 0) {
			throw new Exceptions\CloudApiCall(
				sprintf('Load family things failed: %s', strval($body->offsetGet('msg'))),
				$request,
				$response,
			);
		}

		$data = $body->offsetGet('data');
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
				$devices[] = $data;
			} elseif ($item->offsetGet('itemType') === 3) {
				$groups[] = $data;
			}
		}

		return $this->createEntity(Messages\Response\Cloud\Things::class, Utils\ArrayHash::from([
			'devices' => $devices,
			'groups' => $groups,
		]));
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 */
	private function parseGetThing(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Messages\Response\Cloud\Device|Messages\Response\Cloud\Group
	{
		$body = $this->validateResponseBody($request, $response, self::GET_FAMILY_THINGS_MESSAGE_SCHEMA_FILENAME);

		$error = $body->offsetGet('error');

		if ($error !== 0) {
			throw new Exceptions\CloudApiCall(
				sprintf('Load family specified thing failed: %s', strval($body->offsetGet('msg'))),
				$request,
				$response,
			);
		}

		$data = $body->offsetGet('data');
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
				$devices[] = $this->createEntity(
					Messages\Response\Cloud\Device::class,
					$data,
				);
			} elseif ($item->offsetGet('itemType') === 3) {
				$groups[] = $this->createEntity(
					Messages\Response\Cloud\Group::class,
					$data,
				);
			}
		}

		if (
			(
				$devices !== [] && $groups !== []
			)
			|| (
				$devices === [] && $groups === []
			)
			|| count($devices) > 1
			|| count($groups) > 1
		) {
			throw new Exceptions\CloudApiCall(
				'Load family specified thing failed. Specified thing could not be decoded from response',
				$request,
				$response,
			);
		}

		if ($devices !== []) {
			return $devices[0];
		}

		if ($groups !== []) {
			return $groups[0];
		}

		throw new Exceptions\CloudApiCall(
			'Load family specified thing failed. Specified thing could not be decoded from response',
			$request,
			$response,
		);
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 */
	private function parseGetThingState(
		string $id,
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Messages\Response\Cloud\DeviceState
	{
		$body = $this->validateResponseBody($request, $response, self::GET_THING_STATE_MESSAGE_SCHEMA_FILENAME);

		$error = $body->offsetGet('error');

		if ($error !== 0) {
			throw new Exceptions\CloudApiCall(
				sprintf('Load family specified thing state failed: %s', strval($body->offsetGet('msg'))),
				$request,
				$response,
			);
		}

		$data = $body->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		$params = $data->offsetGet('params');
		assert($params instanceof Utils\ArrayHash);

		return $this->createEntity(
			Messages\Response\Cloud\DeviceState::class,
			Utils\ArrayHash::from([
				'deviceId' => $id,
				'params' => $params,
			]),
		);
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 */
	private function parseSetThingState(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): bool
	{
		$body = $this->validateResponseBody($request, $response, self::SET_THING_STATE_MESSAGE_SCHEMA_FILENAME);

		$error = $body->offsetGet('error');

		if ($error !== 0) {
			throw new Exceptions\CloudApiCall(
				sprintf('Load family specified thing state failed: %s', strval($body->offsetGet('msg'))),
				$request,
				$response,
			);
		}

		return true;
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 */
	private function parseAddThirdPartyDevice(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Messages\Response\Cloud\ThirdPartyDevice
	{
		$body = $this->validateResponseBody($request, $response, self::ADD_THIRD_PARTY_DEVICE_MESSAGE_SCHEMA_FILENAME);

		$error = $body->offsetGet('error');

		if ($error !== 0) {
			throw new Exceptions\CloudApiCall(
				sprintf('Add third party device failed: %s', strval($body->offsetGet('msg'))),
				$request,
				$response,
			);
		}

		$data = $body->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		$thingList = $data->offsetGet('thingList');
		assert($thingList instanceof Utils\ArrayHash);

		$devices = [];

		foreach ($thingList as $item) {
			assert($item instanceof Utils\ArrayHash);

			$data = $item->offsetGet('itemData');
			assert($data instanceof Utils\ArrayHash);

			if (in_array($item->offsetGet('itemType'), [1, 2], true)) {
				$devices[] = $this->createEntity(
					Messages\Response\Cloud\ThirdPartyDevice::class,
					$data,
				);
			}
		}

		if ($devices === [] || count($devices) > 1) {
			throw new Exceptions\CloudApiCall(
				'Add third party device failed. Specified device could not be decoded from response',
				$request,
				$response,
			);
		}

		return $devices[0];
	}

	/**
	 * @throws Exceptions\CloudApiCall
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
			throw new Exceptions\CloudApiCall(
				'Could not get content from response body',
				$request,
				$response,
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
	 * @throws Exceptions\CloudApiError
	 */
	private function createEntity(string $entity, Utils\ArrayHash $data): Messages\Message
	{
		try {
			return $this->entityHelper->create(
				$entity,
				(array) Utils\Json::decode(Utils\Json::encode($data), Utils\Json::FORCE_ARRAY),
			);
		} catch (Exceptions\Runtime $ex) {
			throw new Exceptions\CloudApiError('Could not map data to entity', $ex->getCode(), $ex);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\CloudApiError(
				'Could not create entity from response',
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @return ($throw is true ? Utils\ArrayHash : Utils\ArrayHash|false)
	 *
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
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
				throw new Exceptions\CloudApiCall(
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
	 * @return ($async is true ? Promise\PromiseInterface<Message\ResponseInterface> : Message\ResponseInterface)
	 *
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 */
	private function callRequest(
		Request $request,
		bool $async = true,
	): Promise\PromiseInterface|Message\ResponseInterface
	{
		$deferred = new Promise\Deferred();

		if (
			Utils\Strings::contains(strval($request->getUri()), self::USER_REFRESH_API_ENDPOINT)
			&& $this->tokensAcquired?->diff($this->dateTimeFactory->getNow())->s >= self::ACCESS_TOKEN_VALID_TIME
			&& $this->refreshToken !== null
		) {
			try {
				$this->refreshToken();
			} catch (Exceptions\CloudApiCall $ex) {
				if ($async) {
					return Promise\reject($ex);
				}

				throw $ex;
			}
		}

		$this->logger->debug(
			sprintf(
				'Request: method = %s url = %s',
				$request->getMethod(),
				$request->getUri(),
			),
			[
				'source' => MetadataTypes\Sources\Connector::SONOFF->value,
				'type' => 'cloud-api',
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
									new Exceptions\CloudApiCall(
										'Could not get content from response body',
										$request,
										$response,
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
									'type' => 'cloud-api',
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

							$error = $this->validateResponseBody($request, $response, self::API_ERROR, false);

							if ($error !== false) {
								$errorCode = $error->offsetGet('error');

								if ($errorCode !== 0) {
									$deferred->reject(new Exceptions\CloudApiCall(
										sprintf('Calling api endpoint failed: %s', strval($error->offsetGet('msg'))),
										$request,
										$response,
									));

									return;
								}
							}

							$deferred->resolve($response);
						},
						static function (Throwable $ex) use ($deferred, $request): void {
							$deferred->reject(
								new Exceptions\CloudApiCall(
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

				$error = $this->validateResponseBody($request, $response, self::API_ERROR, false);

				if ($error !== false) {
					$errorCode = $error->offsetGet('error');

					if ($errorCode !== 0) {
						throw new Exceptions\CloudApiCall(
							sprintf('Calling api endpoint failed: %s', strval($error->offsetGet('msg'))),
							$request,
							$response,
						);
					}
				}

				$response->getBody()->rewind();
			} catch (RuntimeException $ex) {
				throw new Exceptions\CloudApiCall(
					'Could not get content from response body',
					$request,
					$response,
					$ex->getCode(),
					$ex,
				);
			}

			$this->logger->debug(
				'Received response',
				[
					'source' => MetadataTypes\Sources\Connector::SONOFF->value,
					'type' => 'cloud-api',
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
			throw new Exceptions\CloudApiCall(
				'Calling api endpoint failed',
				$request,
				null,
				$ex->getCode(),
				$ex,
			);
		}
	}

	private function getApiEndpoint(): Types\CloudApiEndpoint
	{
		if ($this->region === Types\Region::EUROPE) {
			return Types\CloudApiEndpoint::EUROPE;
		}

		if ($this->region === Types\Region::AMERICA) {
			return Types\CloudApiEndpoint::AMERICA;
		}

		if ($this->region === Types\Region::ASIA) {
			return Types\CloudApiEndpoint::ASIA;
		}

		return Types\CloudApiEndpoint::CHINA;
	}

	/**
	 * @throws Exceptions\CloudApiError
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
				throw new Exceptions\CloudApiError('Validation schema for response could not be loaded');
			}
		}

		return $this->validationSchemas[$key];
	}

	/**
	 * @param array<string, string|array<string>>|null $headers
	 * @param array<string, mixed> $params
	 *
	 * @throws Exceptions\CloudApiError
	 */
	private function createRequest(
		string $method,
		string $path,
		array|null $headers = null,
		array $params = [],
		string|null $body = null,
	): Request
	{
		$url = $this->getApiEndpoint()->value . $path;

		if (count($params) > 0) {
			$url .= '?';
			$url .= http_build_query($params);
		}

		try {
			return new Request($method, $url, $headers, $body);
		} catch (Exceptions\InvalidArgument $ex) {
			throw new Exceptions\CloudApiError('Could not create request instance', $ex->getCode(), $ex);
		}
	}

}
