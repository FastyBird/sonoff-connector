<?php declare(strict_types = 1);

/**
 * Auto.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           08.05.23
 */

namespace FastyBird\Connector\Sonoff\Clients;

use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Writers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use InvalidArgumentException;
use Nette;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use Throwable;
use function in_array;

/**
 * Lan client
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Auto extends ClientProcess implements Client
{

	use Nette\SmartObject;

	/** @var array<string> */
	protected array $ignoredLanDevices = [];

	private Lan $lanClient;

	private Cloud $cloudClient;

	public function __construct(
		Entities\SonoffConnector $connector,
		DevicesModels\Devices\DevicesRepository $devicesRepository,
		DevicesUtilities\DeviceConnection $deviceConnectionManager,
		DevicesUtilities\DevicePropertiesStates $devicePropertiesStates,
		DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		DateTimeFactory\Factory $dateTimeFactory,
		EventLoop\LoopInterface $eventLoop,
		LanFactory $lanClientFactory,
		CloudFactory $cloudClientFactory,
		private readonly Writers\Writer $writer,
	)
	{
		parent::__construct(
			$connector,
			$devicesRepository,
			$deviceConnectionManager,
			$devicePropertiesStates,
			$channelPropertiesStates,
			$dateTimeFactory,
			$eventLoop,
		);

		$this->lanClient = $lanClientFactory->create($this->connector, true);
		$this->cloudClient = $cloudClientFactory->create($this->connector, true);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\CloudApiCall
	 * @throws InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function connect(): void
	{
		$this->processedDevices = [];
		$this->processedDevicesCommands = [];

		$this->handlerTimer = null;

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			function (): void {
				$this->registerLoopHandler();
			},
		);

		$this->cloudClient->connect();
		$this->lanClient->connect();

		$this->writer->connect($this->connector, $this);
	}

	public function disconnect(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}

		$this->cloudClient->disconnect();
		$this->lanClient->disconnect();

		$this->writer->disconnect($this->connector, $this);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\LanApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function writeState(
		Entities\SonoffDevice $device,
		string $parameter,
		string|int|float|bool $value,
		string|null $group = null,
		int|null $index = null,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if ($device->getIpAddress() !== null) {
			$this->lanClient->writeState($device, $parameter, $value, $group, $index)
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->otherwise(function () use ($deferred, $device, $parameter, $value, $group, $index): void {
					$this->cloudClient->writeState($device, $parameter, $value, $group, $index)
						->then(static function () use ($deferred): void {
							$deferred->resolve(true);
						})
						->otherwise(static function (Throwable $ex) use ($deferred): void {
							$deferred->reject($ex);
						});
				});
		} else {
			$this->cloudClient->writeState($device, $parameter, $value, $group, $index)
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});
		}

		return $deferred->promise();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\LanApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function readInformation(Entities\SonoffDevice $device): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if ($device->getIpAddress() !== null && !in_array($device->getPlainId(), $this->ignoredDevices, true)) {
			$this->lanClient->readInformation($device)
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->otherwise(function (Throwable $ex) use ($deferred, $device): void {
					if (
						in_array(
							$ex->getCode(),
							[
								API\LanApi::ERROR_INVALID_JSON,
								API\LanApi::ERROR_UNAUTHORIZED,
								API\LanApi::ERROR_DEVICE_ID_INVALID,
								API\LanApi::ERROR_INVALID_PARAMETER,
							],
							true,
						)
					) {
						$this->ignoredLanDevices[] = $device->getPlainId();
					}

					$this->cloudClient->readInformation($device)
						->then(static function () use ($deferred): void {
							$deferred->resolve(true);
						})
						->otherwise(static function (Throwable $ex) use ($deferred): void {
							$deferred->reject($ex);
						});
				});
		} else {
			$this->cloudClient->readInformation($device)
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});
		}

		return $deferred->promise();
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 */
	protected function readStatus(Entities\SonoffDevice $device): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->cloudClient->readStatus($device)
			->then(static function () use ($deferred): void {
				$deferred->resolve(true);
			})
			->otherwise(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

}
