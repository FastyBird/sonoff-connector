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
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use React\EventLoop;
use React\Promise;
use function array_key_exists;
use function in_array;

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

	protected const CMD_STATE = 'state';

	protected const CMD_HEARTBEAT = 'heartbeat';

	/** @var array<string, MetadataDocuments\DevicesModule\Device>  */
	protected array $devices = [];

	/** @var array<string> */
	protected array $processedDevices = [];

	/** @var array<string> */
	protected array $ignoredDevices = [];

	/** @var array<string, array<string, DateTimeInterface|bool>> */
	protected array $processedDevicesCommands = [];

	protected EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		protected readonly Helpers\Device $deviceHelper,
		protected readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		protected readonly DateTimeFactory\Factory $dateTimeFactory,
		protected readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	protected function handleCommunication(): void
	{
		foreach ($this->devices as $device) {
			if (!in_array($device->getId()->toString(), $this->processedDevices, true)) {
				$this->processedDevices[] = $device->getId()->toString();

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
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	protected function processDevice(MetadataDocuments\DevicesModule\Device $device): bool
	{
		if ($this->readDeviceInformation($device)) {
			return true;
		}

		return $this->readDeviceState($device);
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	protected function readDeviceInformation(MetadataDocuments\DevicesModule\Device $device): bool
	{
		if (!array_key_exists($device->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getId()->toString()] = [];
		}

		if (array_key_exists(self::CMD_HEARTBEAT, $this->processedDevicesCommands[$device->getId()->toString()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getId()->toString()][self::CMD_HEARTBEAT];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp()
					< $this->deviceHelper->getHeartbeatDelay($device)
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();

		$deviceState = $this->deviceConnectionManager->getState($device);

		if ($deviceState->equalsValue(MetadataTypes\ConnectionState::STATE_ALERT)) {
			unset($this->devices[$device->getId()->toString()]);

			return false;
		}

		$this->readInformation($device)
			->then(function () use ($device): void {
				$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();
			});

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	protected function readDeviceState(MetadataDocuments\DevicesModule\Device $device): bool
	{
		if (!array_key_exists($device->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getId()->toString()] = [];
		}

		if (array_key_exists(self::CMD_STATE, $this->processedDevicesCommands[$device->getId()->toString()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp()
					< $this->deviceHelper->getStateReadingDelay($device)
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->dateTimeFactory->getNow();

		$deviceState = $this->deviceConnectionManager->getState($device);

		if ($deviceState->equalsValue(MetadataTypes\ConnectionState::STATE_ALERT)) {
			unset($this->devices[$device->getId()->toString()]);

			return false;
		}

		$this->readState($device)
			->then(function () use ($device): void {
				$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->dateTimeFactory->getNow();
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

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	abstract protected function readInformation(
		MetadataDocuments\DevicesModule\Device $device,
	): Promise\PromiseInterface;

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	abstract protected function readState(MetadataDocuments\DevicesModule\Device $device): Promise\PromiseInterface;

}
