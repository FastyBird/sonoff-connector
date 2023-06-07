<?php declare(strict_types = 1);

/**
 * ClientProcess.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           06.05.23
 */

namespace FastyBird\Connector\Sonoff\Clients;

use DateTimeInterface;
use Exception;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use React\EventLoop;
use React\Promise;
use function array_key_exists;
use function assert;
use function in_array;
use function intval;
use function preg_match;

/**
 * Client process methods
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class ClientProcess
{

	use Nette\SmartObject;

	protected const HANDLER_START_DELAY = 2.0;

	protected const HANDLER_PROCESSING_INTERVAL = 0.01;

	protected const HEARTBEAT_DELAY = 600;

	protected const CMD_STATUS = 'status';

	protected const CMD_HEARTBEAT = 'hearbeat';

	protected const MATCH_CHANNEL_GROUP = '/^(?P<group>[a-zA-Z]+)_(?P<index>[0-9]+)$/';

	/** @var array<string> */
	protected array $processedDevices = [];

	/** @var array<string> */
	protected array $ignoredDevices = [];

	/** @var array<string, array<string, DateTimeInterface|bool>> */
	protected array $processedDevicesCommands = [];

	protected EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		protected readonly Entities\SonoffConnector $connector,
		protected readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		protected readonly DevicesUtilities\DevicePropertiesStates $devicePropertiesStates,
		protected readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		protected readonly DateTimeFactory\Factory $dateTimeFactory,
		protected readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeDeviceProperty(
		Entities\SonoffDevice $device,
		DevicesEntities\Devices\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
		$state = $this->devicePropertiesStates->getValue($property);

		return $this->writeProperty($state, $device, $property);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeChannelProperty(
		Entities\SonoffDevice $device,
		DevicesEntities\Channels\Channel $channel,
		DevicesEntities\Channels\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
		$state = $this->channelPropertiesStates->getValue($property);

		$group = $index = null;

		if (preg_match(self::MATCH_CHANNEL_GROUP, $channel->getIdentifier(), $matches) === 1) {
			$group = API\Transformer::channelIdentifierToGroup($matches['group'], $property->getIdentifier());
			$index = intval($matches['index']);
		}

		return $this->writeProperty($state, $device, $property, $group, $index);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function writeProperty(
		DevicesStates\DeviceProperty|DevicesStates\ChannelProperty|null $state,
		Entities\SonoffDevice $device,
		DevicesEntities\Devices\Properties\Dynamic|DevicesEntities\Channels\Properties\Dynamic $property,
		string|null $group = null,
		int|null $index = null,
	): Promise\PromiseInterface
	{
		if ($state === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Property state could not be found. Nothing to write'),
			);
		}

		if (!$property->isSettable()) {
			return Promise\reject(new Exceptions\InvalidArgument('Provided property is not writable'));
		}

		if ($state->getExpectedValue() === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Property expected value is not set. Nothing to write'),
			);
		}

		$valueToWrite = API\Transformer::transformValueToDevice(
			$property->getDataType(),
			$property->getFormat(),
			$state->getExpectedValue(),
		);

		if ($valueToWrite === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Property expected value could not be transformed to device'),
			);
		}

		if ($state->isPending() === true) {
			return $this->writeState(
				$device,
				API\Transformer::devicePropertyToParameterName($property->getIdentifier()),
				$valueToWrite,
				$group,
				$index,
			);
		}

		return Promise\reject(new Exceptions\InvalidArgument('Provided property state is in invalid state'));
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Exception
	 */
	protected function handleCommunication(): void
	{
		$findDevicesQuery = new DevicesQueries\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery, Entities\SonoffDevice::class) as $device) {
			assert($device instanceof Entities\SonoffDevice);

			if (
				!in_array($device->getPlainId(), $this->processedDevices, true)
				&& !in_array($device->getPlainId(), $this->ignoredDevices, true)
				&& !$this->deviceConnectionManager->getState($device)->equalsValue(
					MetadataTypes\ConnectionState::STATE_STOPPED,
				)
			) {
				$this->processedDevices[] = $device->getPlainId();

				if ($this->processDevice($device)) {
					$this->registerLoopHandler();

					return;
				}
			}
		}

		$this->processedDevices = [];

		$this->registerLoopHandler();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Exception
	 */
	protected function processDevice(Entities\SonoffDevice $device): bool
	{
		if ($this->readDeviceInformation($device)) {
			return true;
		}

		return $this->readDeviceStatus($device);
	}

	protected function readDeviceInformation(Entities\SonoffDevice $device): bool
	{
		if (!array_key_exists($device->getPlainId(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getPlainId()] = [];
		}

		if (array_key_exists(self::CMD_HEARTBEAT, $this->processedDevicesCommands[$device->getPlainId()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getPlainId()][self::CMD_HEARTBEAT];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp() < self::HEARTBEAT_DELAY
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getPlainId()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();

		$this->readInformation($device)
			->then(function () use ($device): void {
				$this->processedDevicesCommands[$device->getPlainId()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();
			});

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function readDeviceStatus(Entities\SonoffDevice $device): bool
	{
		if (!array_key_exists($device->getPlainId(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getPlainId()] = [];
		}

		if (array_key_exists(self::CMD_STATUS, $this->processedDevicesCommands[$device->getPlainId()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getPlainId()][self::CMD_STATUS];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp() < $device->getStatusReadingDelay()
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getPlainId()][self::CMD_STATUS] = $this->dateTimeFactory->getNow();

		$this->readStatus($device)
			->then(function () use ($device): void {
				$this->processedDevicesCommands[$device->getPlainId()][self::CMD_STATUS] = $this->dateTimeFactory->getNow();
			});

		return true;
	}

	protected function registerLoopHandler(): void
	{
		$this->handlerTimer = $this->eventLoop->addTimer(
			self::HANDLER_PROCESSING_INTERVAL,
			function (): void {
				$this->handleCommunication();
			},
		);
	}

	abstract protected function readInformation(Entities\SonoffDevice $device): Promise\PromiseInterface;

	abstract protected function readStatus(Entities\SonoffDevice $device): Promise\PromiseInterface;

	abstract protected function writeState(
		Entities\SonoffDevice $device,
		string $parameter,
		string|int|float|bool $value,
		string|null $group = null,
		int|null $index = null,
	): Promise\PromiseInterface;

}
