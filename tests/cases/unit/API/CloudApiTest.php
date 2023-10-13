<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\API;

use Error;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Services;
use FastyBird\Connector\Sonoff\Tests;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp;
use Nette\DI;
use Nette\Utils;
use Psr\Http;
use RuntimeException;
use function strval;

final class CloudApiTest extends Tests\Cases\Unit\DbTestCase
{

	private const USERNAME = 'user@username.com';

	private const PASSWORD = 'dBCQZohQNR2U4rW9';

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\CloudApiCall
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetFamily(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					if (strval($request->getUri()) === 'https://eu-apia.coolkit.cc/v2/user/login') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/cloud_api_user_login.json',
								),
							);
					} else {
						self::assertSame(
							'https://eu-apia.coolkit.cc/v2/family',
							strval($request->getUri()),
						);
						self::assertSame(
							RequestMethodInterface::METHOD_GET,
							$request->getMethod(),
						);

						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/cloud_api_get_family.json',
								),
							);
					}

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$cloudApiFactory = $this->getContainer()->getByType(API\CloudApiFactory::class);

		$cloudApi = $cloudApiFactory->create(
			self::USERNAME,
			self::PASSWORD,
			Sonoff\Constants::DEFAULT_APP_ID,
			Sonoff\Constants::DEFAULT_APP_SECRET,
		);

		$family = $cloudApi->getFamily(false);

		self::assertSame('15483473418e0ab1093dbf66', $family->getFamilyId());
		self::assertCount(1, $family->getHomes());
		self::assertSame([
			'family_id' => '15483473418e0ab1093dbf66',
			'homes' => [
				0 => [
					'id' => '15483473418e0ab1093dbf66',
					'api_key' => '211d138d-14a1-433a-813a-1e945d4f259b',
					'name' => 'My Home',
					'index' => 0,
					'rooms' => [
						0 => [
							'id' => '1478f7e38768ae00085229ef',
							'name' => 'Room one',
							'index' => -1,
						],
						1 => [
							'id' => '12483473418eaa00093dbf63',
							'name' => 'Room two',
							'index' => 0,
						],
						2 => [
							'id' => '12483473418eaa00093dbf64',
							'name' => 'Other room',
							'index' => 1,
						],
						3 => [
							'id' => '12483473418eaa00093dbf65',
							'name' => 'Living room',
							'index' => 2,
						],
						4 => [
							'id' => '1478f8dd9ff83a0009450de9',
							'name' => 'Guests room',
							'index' => -2,
						],
					],
				],
			],
		], $family->toArray());
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\CloudApiCall
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetFamilyThings(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					if (strval($request->getUri()) === 'https://eu-apia.coolkit.cc/v2/user/login') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/cloud_api_user_login.json',
								),
							);
					} else {
						self::assertSame(
							'https://eu-apia.coolkit.cc/v2/device/thing?num=0&familyId=15483473418e0ab1093dbf66',
							strval($request->getUri()),
						);
						self::assertSame(
							RequestMethodInterface::METHOD_GET,
							$request->getMethod(),
						);

						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/cloud_api_get_family_things.json',
								),
							);
					}

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$cloudApiFactory = $this->getContainer()->getByType(API\CloudApiFactory::class);

		$cloudApi = $cloudApiFactory->create(
			self::USERNAME,
			self::PASSWORD,
			Sonoff\Constants::DEFAULT_APP_ID,
			Sonoff\Constants::DEFAULT_APP_SECRET,
		);

		$things = $cloudApi->getFamilyThings('15483473418e0ab1093dbf66', false);

		self::assertCount(16, $things->getDevices());
		self::assertCount(1, $things->getGroups());
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\CloudApiCall
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetDevice(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					if (strval($request->getUri()) === 'https://eu-apia.coolkit.cc/v2/user/login') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/cloud_api_user_login.json',
								),
							);
					} else {
						self::assertSame(
							'https://eu-apia.coolkit.cc/v2/device/thing',
							strval($request->getUri()),
						);
						self::assertSame(
							RequestMethodInterface::METHOD_POST,
							$request->getMethod(),
						);

						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/cloud_api_get_thing_state.json',
								),
							);
					}

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$cloudApiFactory = $this->getContainer()->getByType(API\CloudApiFactory::class);

		$cloudApi = $cloudApiFactory->create(
			self::USERNAME,
			self::PASSWORD,
			Sonoff\Constants::DEFAULT_APP_ID,
			Sonoff\Constants::DEFAULT_APP_SECRET,
		);

		$device = $cloudApi->getThing('1000191aa7', 1, false);

		self::assertSame('1000191aa7', $device->getDeviceId());
		self::assertInstanceOf(Sonoff\Entities\Uiid\Uiid1::class, $device->getState());
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\CloudApiCall
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testSetDevice(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					if (strval($request->getUri()) === 'https://eu-apia.coolkit.cc/v2/user/login') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/cloud_api_user_login.json',
								),
							);
					} else {
						self::assertSame(
							'https://eu-apia.coolkit.cc/v2/device/thing/status',
							strval($request->getUri()),
						);
						self::assertSame(
							RequestMethodInterface::METHOD_POST,
							$request->getMethod(),
						);

						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/cloud_api_set_thing_state.json',
								),
							);
					}

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$cloudApiFactory = $this->getContainer()->getByType(API\CloudApiFactory::class);

		$cloudApi = $cloudApiFactory->create(
			self::USERNAME,
			self::PASSWORD,
			Sonoff\Constants::DEFAULT_APP_ID,
			Sonoff\Constants::DEFAULT_APP_SECRET,
		);

		$result = $cloudApi->setThingState('1000191aa7', 'switch', 'off', 'switches', 0, 1, false);

		self::assertTrue($result);
	}

}
