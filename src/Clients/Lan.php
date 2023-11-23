<?php declare(strict_types = 1);

/**
 * Lan.php
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

use BadMethodCallException;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Psr\EventDispatcher;
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
final class Lan extends ClientProcess implements Client
{

	/**
	 * @param DevicesModels\Configuration\Devices\Repository<MetadataDocuments\DevicesModule\Device> $devicesConfigurationRepository
	 */
	public function __construct(
		Helpers\Device $deviceHelper,
		DevicesUtilities\DeviceConnection $deviceConnectionManager,
		DateTimeFactory\Factory $dateTimeFactory,
		EventLoop\LoopInterface $eventLoop,
		private readonly MetadataDocuments\DevicesModule\Connector $connector,
		private readonly bool $autoMode,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\Entity $entityHelper,
		private readonly Queue\Queue $queue,
		private readonly Sonoff\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		parent::__construct(
			$deviceHelper,
			$deviceConnectionManager,
			$dateTimeFactory,
			$eventLoop,
		);
	}

	/**
	 * @throws BadMethodCallException
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function connect(): void
	{
		if (!$this->autoMode) {
			$this->processedDevices = [];
			$this->processedDevicesCommands = [];

			$this->handlerTimer = null;

			$this->eventLoop->addTimer(
				self::HANDLER_START_DELAY,
				function (): void {
					$this->registerLoopHandler();
				},
			);

			$findDevicesQuery = new DevicesQueries\Configuration\FindDevices();
			$findDevicesQuery->forConnector($this->connector);

			foreach ($this->devicesConfigurationRepository->findAllBy($findDevicesQuery) as $device) {
				$this->devices[$device->getId()->toString()] = $device;
			}
		}

		$this->connectionManager->getLanConnection()->connect();

		$this->connectionManager
			->getLanConnection()
			->on('message', function (Entities\API\Lan\DeviceEvent $message): void {
				$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
				$findDeviceQuery->byIdentifier($message->getId());

				$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

				if ($device !== null) {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::get(
									MetadataTypes\ConnectionState::STATE_CONNECTED,
								),
							],
						),
					);

					$this->handleDeviceEvent($device, $message);
				}
			});

		$findDevicesQuery = new DevicesQueries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		foreach ($this->devicesConfigurationRepository->findAllBy($findDevicesQuery) as $device) {
			if ($this->deviceHelper->getDeviceKey($device) !== null) {
				$this->connectionManager
					->getLanConnection()
					->registerDeviceKey($device->getIdentifier(), $this->deviceHelper->getDeviceKey($device));
			}
		}
	}

	public function disconnect(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}

		$this->connectionManager->getLanConnection()->disconnect();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	protected function readInformation(MetadataDocuments\DevicesModule\Device $device): Promise\PromiseInterface
	{
		if ($this->deviceHelper->getIpAddress($device) === null) {
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $device->getConnector(),
						'identifier' => $device->getIdentifier(),
						'state' => MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_ALERT),
					],
				),
			);

			return Promise\reject(new Exceptions\InvalidState('Device ip address is not configured'));
		}

		$deferred = new Promise\Deferred();

		$this->connectionManager
			->getLanConnection()
			->getDeviceInfo(
				$device->getIdentifier(),
				$this->deviceHelper->getIpAddress($device),
				$this->deviceHelper->getPort($device),
			)
			->then(function (Entities\API\Lan\DeviceInfo $result) use ($deferred, $device): void {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::get(
									MetadataTypes\ConnectionState::STATE_CONNECTED,
								),
							],
						),
					);

					$this->handleDeviceInfo($result);

					$deferred->resolve(true);
			})
				->catch(function (Throwable $ex) use ($deferred, $device): void {
					if ($ex instanceof Exceptions\LanApiCall) {
						$this->checkError($ex, $device);

						$this->logger->warning(
							'Calling device lan api for reading state failed',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
								'type' => 'lan-client',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
								'device' => [
									'id' => $device->getId()->toString(),
								],
							],
						);
					} else {
						$this->logger->error(
							'Could not call device lan api',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
								'type' => 'lan-client',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
								'device' => [
									'id' => $device->getId()->toString(),
								],
							],
						);

						$this->dispatcher?->dispatch(
							new DevicesEvents\TerminateConnector(
								MetadataTypes\ConnectorSource::get(
									MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
								),
								'Could not call device lan api',
								$ex,
							),
						);
					}

					$deferred->reject($ex);
				});

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	protected function readState(MetadataDocuments\DevicesModule\Device $device): Promise\PromiseInterface
	{
		// Reading device state is not supported by LAN api
		return Promise\resolve(true);
	}

	private function checkError(Exceptions\LanApiCall $ex, MetadataDocuments\DevicesModule\Device $device): void
	{
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
			&& !$this->autoMode
		) {
			$this->ignoredDevices[] = $device->getId()->toString();
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function handleDeviceEvent(
		MetadataDocuments\DevicesModule\Device $device,
		Entities\API\Lan\DeviceEvent $event,
	): void
	{
		if ($event->getData() === null) {
			return;
		}

		// Special handling for Sonoff SPM devices acting as sub-devices
		if ($event->getData()->getSubDeviceId() !== null) {
			$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
			$findDeviceQuery->forParent($device);
			$findDeviceQuery->byIdentifier($event->getData()->getSubDeviceId());

			$subDevice = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

			if ($subDevice === null) {
				$this->logger->error(
					'Sonoff SPM sub-device could not be found',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'lan-client',
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
						],
						'sub_device' => [
							'identifier' => $event->getData()->getSubDeviceId(),
						],
					],
				);

				return;
			}

			$device = $subDevice;
		}

		$states = [];

		if ($event->getData()->isSwitch() || $event->getData()->isLight()) {
			if ($event->getData()->getSwitch() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::SWITCH,
					Types\PropertyParameter::VALUE => $event->getData()->getSwitch(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH,
				];
			}

			if ($event->getData()->getStartup() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::STARTUP,
					Types\PropertyParameter::VALUE => $event->getData()->getStartup(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH,
				];
			}

			if ($event->getData()->getPulse() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::PULSE,
					Types\PropertyParameter::VALUE => $event->getData()->getPulse(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH,
				];
			}

			if ($event->getData()->getPulseWidth() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::PULSE_WIDTH,
					Types\PropertyParameter::VALUE => $event->getData()->getPulseWidth(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH,
				];
			}

			if ($event->getData()->getBrightness() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::BRIGHTNESS_2,
					Types\PropertyParameter::VALUE => $event->getData()->getBrightness(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
				];
			}

			if ($event->getData()->getMinimumBrightness() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::MINIMUM_BRIGHTNESS,
					Types\PropertyParameter::VALUE => $event->getData()->getMinimumBrightness(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
				];
			}

			if ($event->getData()->getMaximumBrightness() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::MAXIMUM_BRIGHTNESS,
					Types\PropertyParameter::VALUE => $event->getData()->getMaximumBrightness(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
				];
			}

			if ($event->getData()->getMode() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::MODE,
					Types\PropertyParameter::VALUE => $event->getData()->getMode(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
				];
			}
		} elseif ($event->getData()->isSwitches()) {
			foreach ($event->getData()->getSwitchesStates() as $switchState) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::SWITCH,
					Types\PropertyParameter::VALUE => $switchState->getSwitch(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH . '_' . $switchState->getOutlet(),
				];
			}
		}

		if ($event->getData()->getRssi() !== null) {
			$states[] = [
				Types\PropertyParameter::NAME => Types\Parameter::RSSI,
				Types\PropertyParameter::VALUE => $event->getData()->getRssi(),
			];
		}

		if ($event->getData()->getSsid() !== null) {
			$states[] = [
				Types\PropertyParameter::NAME => Types\Parameter::SSID,
				Types\PropertyParameter::VALUE => $event->getData()->getSsid(),
			];
		}

		if ($event->getData()->getBssid() !== null) {
			$states[] = [
				Types\PropertyParameter::NAME => Types\Parameter::BSSID,
				Types\PropertyParameter::VALUE => $event->getData()->getBssid(),
			];
		}

		if ($event->getData()->getFirmwareVersion() !== null) {
			$states[] = [
				Types\PropertyParameter::NAME => Types\Parameter::FIRMWARE_VERSION,
				Types\PropertyParameter::VALUE => $event->getData()->getFirmwareVersion(),
			];
		}

		if ($event->getData()->getStatusLed() !== null) {
			$states[] = [
				Types\PropertyParameter::NAME => Types\Parameter::STATUS_LED,
				Types\PropertyParameter::VALUE => $event->getData()->getStatusLed(),
			];
		}

		if ($states === []) {
			return;
		}

		$this->queue->append(
			$this->entityHelper->create(
				Entities\Messages\StoreParametersStates::class,
				[
					'connector' => $this->connector->getId(),
					'identifier' => $device->getIdentifier(),
					'parameters' => $states,
				],
			),
		);
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	private function handleDeviceInfo(Entities\API\Lan\DeviceInfo $info): void
	{
		$states = [];

		if ($info->isSwitch() || $info->isLight()) {
			if ($info->getSwitch() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::SWITCH,
					Types\PropertyParameter::VALUE => $info->getSwitch(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH,
				];
			}

			if ($info->getStartup() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::STARTUP,
					Types\PropertyParameter::VALUE => $info->getStartup(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH,
				];
			}

			if ($info->getPulse() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::PULSE,
					Types\PropertyParameter::VALUE => $info->getPulse(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH,
				];
			}

			if ($info->getPulseWidth() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::PULSE_WIDTH,
					Types\PropertyParameter::VALUE => $info->getPulseWidth(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH,
				];
			}

			if ($info->getBrightness() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::BRIGHTNESS_2,
					Types\PropertyParameter::VALUE => $info->getBrightness(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
				];
			}

			if ($info->getMinimumBrightness() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::MINIMUM_BRIGHTNESS,
					Types\PropertyParameter::VALUE => $info->getMinimumBrightness(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
				];
			}

			if ($info->getMaximumBrightness() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::MAXIMUM_BRIGHTNESS,
					Types\PropertyParameter::VALUE => $info->getMaximumBrightness(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
				];
			}

			if ($info->getMode() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::MODE,
					Types\PropertyParameter::VALUE => $info->getMode(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
				];
			}
		} elseif ($info->isSwitches()) {
			foreach ($info->getSwitchesStates() as $switchState) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::SWITCH,
					Types\PropertyParameter::VALUE => $switchState->getSwitch(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH . '_' . $switchState->getOutlet(),
				];
			}

			foreach ($info->getSwitchesPulses() as $switchPulse) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::PULSE,
					Types\PropertyParameter::VALUE => $switchPulse->getPulse(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH . '_' . $switchPulse->getOutlet(),
				];

				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::PULSE_WIDTH,
					Types\PropertyParameter::VALUE => $switchPulse->getWidth(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH . '_' . $switchPulse->getOutlet(),
				];
			}

			foreach ($info->getSwitchesConfiguration() as $switchConfiguration) {
				$states[] = [
					Types\PropertyParameter::NAME => Types\Parameter::STARTUP,
					Types\PropertyParameter::VALUE => $switchConfiguration->getStartup(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH . '_' . $switchConfiguration->getOutlet(),
				];
			}
		}

		$states[] = [
			Types\PropertyParameter::NAME => Types\Parameter::RSSI,
			Types\PropertyParameter::VALUE => $info->getRssi(),
		];

		$states[] = [
			Types\PropertyParameter::NAME => Types\Parameter::SSID,
			Types\PropertyParameter::VALUE => $info->getSsid(),
		];

		$states[] = [
			Types\PropertyParameter::NAME => Types\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS,
			Types\PropertyParameter::VALUE => $info->getBssid(),
		];

		$states[] = [
			Types\PropertyParameter::NAME => Types\Parameter::FIRMWARE_VERSION,
			Types\PropertyParameter::VALUE => $info->getFirmwareVersion(),
		];

		if ($info->getStatusLed() !== null) {
			$states[] = [
				Types\PropertyParameter::NAME => Types\Parameter::STATUS_LED,
				Types\PropertyParameter::VALUE => $info->getStatusLed(),
			];
		}

		$this->queue->append(
			$this->entityHelper->create(
				Entities\Messages\StoreParametersStates::class,
				[
					'connector' => $this->connector->getId(),
					'identifier' => $info->getId(),
					'parameters' => $states,
				],
			),
		);
	}

}
