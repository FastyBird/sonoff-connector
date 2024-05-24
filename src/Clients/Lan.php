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
use FastyBird\Connector\Sonoff\Documents;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queries;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Psr\EventDispatcher;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use Throwable;
use TypeError;
use ValueError;
use function in_array;
use function React\Async\async;

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

	public function __construct(
		Helpers\Device $deviceHelper,
		DevicesUtilities\DeviceConnection $deviceConnectionManager,
		DateTimeFactory\Factory $dateTimeFactory,
		EventLoop\LoopInterface $eventLoop,
		private readonly Documents\Connectors\Connector $connector,
		private readonly bool $autoMode,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\MessageBuilder $entityHelper,
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
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Mapping
	 * @throws RuntimeException
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function connect(): void
	{
		if (!$this->autoMode) {
			$this->processedDevices = [];
			$this->processedDevicesCommands = [];

			$this->handlerTimer = null;

			$this->eventLoop->addTimer(
				self::HANDLER_START_DELAY,
				async(function (): void {
					$this->registerLoopHandler();
				}),
			);

			$findDevicesQuery = new Queries\Configuration\FindDevices();
			$findDevicesQuery->forConnector($this->connector);

			$devices = $this->devicesConfigurationRepository->findAllBy(
				$findDevicesQuery,
				Documents\Devices\Device::class,
			);

			foreach ($devices as $device) {
				$this->devices[$device->getId()->toString()] = $device;
			}
		}

		$this->connectionManager->getLanConnection()->connect();

		$lanClient = $this->connectionManager->getLanConnection();

		$lanClient->onMessage[] = function (API\Messages\Message $message): void {
			if ($message instanceof API\Messages\Response\Lan\DeviceEvent) {
				$findDeviceQuery = new Queries\Configuration\FindDevices();
				$findDeviceQuery->byIdentifier($message->getId());

				$device = $this->devicesConfigurationRepository->findOneBy(
					$findDeviceQuery,
					Documents\Devices\Device::class,
				);

				if ($device !== null) {
					$this->queue->append(
						$this->entityHelper->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::CONNECTED,
							],
						),
					);

					$this->handleDeviceEvent($device, $message);
				}
			}
		};

		$findDevicesQuery = new Queries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		$devices = $this->devicesConfigurationRepository->findAllBy(
			$findDevicesQuery,
			Documents\Devices\Device::class,
		);

		foreach ($devices as $device) {
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function readInformation(
		Documents\Devices\Device $device,
	): Promise\PromiseInterface
	{
		if ($this->deviceHelper->getIpAddress($device) === null) {
			$this->queue->append(
				$this->entityHelper->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $device->getConnector(),
						'identifier' => $device->getIdentifier(),
						'state' => DevicesTypes\ConnectionState::ALERT,
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
			->then(function (API\Messages\Response\Lan\DeviceInfo $result) use ($deferred, $device): void {
					$this->queue->append(
						$this->entityHelper->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::CONNECTED,
							],
						),
					);

					$this->handleDeviceInfo($result);

					$deferred->resolve(true);
			})
				->catch(function (Throwable $ex) use ($deferred, $device): void {
					if ($ex instanceof Exceptions\LanApiError) {
						$this->queue->append(
							$this->entityHelper->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $device->getConnector(),
									'identifier' => $device->getIdentifier(),
									'state' => DevicesTypes\ConnectionState::ALERT,
								],
							),
						);

						$this->logger->warning(
							'Calling device lan api for reading state failed',
							[
								'source' => MetadataTypes\Sources\Connector::SONOFF->value,
								'type' => 'lan-client',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
								'device' => [
									'id' => $device->getId()->toString(),
								],
							],
						);
					} elseif ($ex instanceof Exceptions\LanApiCall) {
						$this->checkError($ex, $device);

						$this->logger->warning(
							'Calling device lan api for reading state failed',
							[
								'source' => MetadataTypes\Sources\Connector::SONOFF->value,
								'type' => 'lan-client',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
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
								'source' => MetadataTypes\Sources\Connector::SONOFF->value,
								'type' => 'lan-client',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
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
								MetadataTypes\Sources\Connector::SONOFF,
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
	protected function readState(Documents\Devices\Device $device): Promise\PromiseInterface
	{
		// Reading device state is not supported by LAN api
		return Promise\resolve(true);
	}

	private function checkError(
		Exceptions\LanApiCall $ex,
		Documents\Devices\Device $device,
	): void
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
	 */
	private function handleDeviceEvent(
		Documents\Devices\Device $device,
		API\Messages\Response\Lan\DeviceEvent $event,
	): void
	{
		if ($event->getData() === null) {
			return;
		}

		// Special handling for Sonoff SPM devices acting as sub-devices
		if ($event->getData()->getSubDeviceId() !== null) {
			$findDeviceQuery = new Queries\Configuration\FindDevices();
			$findDeviceQuery->forParent($device);
			$findDeviceQuery->byIdentifier($event->getData()->getSubDeviceId());

			$subDevice = $this->devicesConfigurationRepository->findOneBy(
				$findDeviceQuery,
				Documents\Devices\Device::class,
			);

			if ($subDevice === null) {
				$this->logger->error(
					'Sonoff SPM sub-device could not be found',
					[
						'source' => MetadataTypes\Sources\Connector::SONOFF->value,
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
					Types\PropertyParameter::NAME->value => Types\Parameter::SWITCH->value,
					Types\PropertyParameter::VALUE->value => $event->getData()->getSwitch(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
				];
			}

			if ($event->getData()->getStartup() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::STARTUP->value,
					Types\PropertyParameter::VALUE->value => $event->getData()->getStartup(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
				];
			}

			if ($event->getData()->getPulse() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::PULSE->value,
					Types\PropertyParameter::VALUE->value => $event->getData()->getPulse(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
				];
			}

			if ($event->getData()->getPulseWidth() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::PULSE_WIDTH->value,
					Types\PropertyParameter::VALUE->value => $event->getData()->getPulseWidth(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
				];
			}

			if ($event->getData()->getBrightness() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::BRIGHTNESS_2->value,
					Types\PropertyParameter::VALUE->value => $event->getData()->getBrightness(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
				];
			}

			if ($event->getData()->getMinimumBrightness() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::MINIMUM_BRIGHTNESS->value,
					Types\PropertyParameter::VALUE->value => $event->getData()->getMinimumBrightness(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
				];
			}

			if ($event->getData()->getMaximumBrightness() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::MAXIMUM_BRIGHTNESS->value,
					Types\PropertyParameter::VALUE->value => $event->getData()->getMaximumBrightness(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
				];
			}

			if ($event->getData()->getMode() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::MODE->value,
					Types\PropertyParameter::VALUE->value => $event->getData()->getMode(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
				];
			}
		} elseif ($event->getData()->isSwitches()) {
			foreach ($event->getData()->getSwitchesStates() as $switchState) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::SWITCH->value,
					Types\PropertyParameter::VALUE->value => $switchState->getSwitch(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value . '_' . $switchState->getOutlet(),
				];
			}
		}

		if ($event->getData()->getRssi() !== null) {
			$states[] = [
				Types\PropertyParameter::NAME->value => Types\Parameter::RSSI->value,
				Types\PropertyParameter::VALUE->value => $event->getData()->getRssi(),
			];
		}

		if ($event->getData()->getSsid() !== null) {
			$states[] = [
				Types\PropertyParameter::NAME->value => Types\Parameter::SSID->value,
				Types\PropertyParameter::VALUE->value => $event->getData()->getSsid(),
			];
		}

		if ($event->getData()->getBssid() !== null) {
			$states[] = [
				Types\PropertyParameter::NAME->value => Types\Parameter::BSSID->value,
				Types\PropertyParameter::VALUE->value => $event->getData()->getBssid(),
			];
		}

		if ($event->getData()->getFirmwareVersion() !== null) {
			$states[] = [
				Types\PropertyParameter::NAME->value => Types\Parameter::FIRMWARE_VERSION->value,
				Types\PropertyParameter::VALUE->value => $event->getData()->getFirmwareVersion(),
			];
		}

		if ($event->getData()->getStatusLed() !== null) {
			$states[] = [
				Types\PropertyParameter::NAME->value => Types\Parameter::STATUS_LED->value,
				Types\PropertyParameter::VALUE->value => $event->getData()->getStatusLed(),
			];
		}

		if ($states === []) {
			return;
		}

		$this->queue->append(
			$this->entityHelper->create(
				Queue\Messages\StoreParametersStates::class,
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
	private function handleDeviceInfo(API\Messages\Response\Lan\DeviceInfo $info): void
	{
		$states = [];

		if ($info->isSwitch() || $info->isLight()) {
			if ($info->getSwitch() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::SWITCH->value,
					Types\PropertyParameter::VALUE->value => $info->getSwitch(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
				];
			}

			if ($info->getStartup() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::STARTUP->value,
					Types\PropertyParameter::VALUE->value => $info->getStartup(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
				];
			}

			if ($info->getPulse() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::PULSE->value,
					Types\PropertyParameter::VALUE->value => $info->getPulse(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
				];
			}

			if ($info->getPulseWidth() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::PULSE_WIDTH->value,
					Types\PropertyParameter::VALUE->value => $info->getPulseWidth(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
				];
			}

			if ($info->getBrightness() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::BRIGHTNESS_2->value,
					Types\PropertyParameter::VALUE->value => $info->getBrightness(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
				];
			}

			if ($info->getMinimumBrightness() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::MINIMUM_BRIGHTNESS->value,
					Types\PropertyParameter::VALUE->value => $info->getMinimumBrightness(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
				];
			}

			if ($info->getMaximumBrightness() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::MAXIMUM_BRIGHTNESS->value,
					Types\PropertyParameter::VALUE->value => $info->getMaximumBrightness(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
				];
			}

			if ($info->getMode() !== null) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::MODE->value,
					Types\PropertyParameter::VALUE->value => $info->getMode(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
				];
			}
		} elseif ($info->isSwitches()) {
			foreach ($info->getSwitchesStates() as $switchState) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::SWITCH->value,
					Types\PropertyParameter::VALUE->value => $switchState->getSwitch(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value . '_' . $switchState->getOutlet(),
				];
			}

			foreach ($info->getSwitchesPulses() as $switchPulse) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::PULSE->value,
					Types\PropertyParameter::VALUE->value => $switchPulse->getPulse(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value . '_' . $switchPulse->getOutlet(),
				];

				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::PULSE_WIDTH->value,
					Types\PropertyParameter::VALUE->value => $switchPulse->getWidth(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value . '_' . $switchPulse->getOutlet(),
				];
			}

			foreach ($info->getSwitchesConfiguration() as $switchConfiguration) {
				$states[] = [
					Types\PropertyParameter::NAME->value => Types\Parameter::STARTUP->value,
					Types\PropertyParameter::VALUE->value => $switchConfiguration->getStartup(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value . '_' . $switchConfiguration->getOutlet(),
				];
			}
		}

		$states[] = [
			Types\PropertyParameter::NAME->value => Types\Parameter::RSSI->value,
			Types\PropertyParameter::VALUE->value => $info->getRssi(),
		];

		$states[] = [
			Types\PropertyParameter::NAME->value => Types\Parameter::SSID->value,
			Types\PropertyParameter::VALUE->value => $info->getSsid(),
		];

		$states[] = [
			Types\PropertyParameter::NAME->value => Types\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value,
			Types\PropertyParameter::VALUE->value => $info->getBssid(),
		];

		$states[] = [
			Types\PropertyParameter::NAME->value => Types\Parameter::FIRMWARE_VERSION->value,
			Types\PropertyParameter::VALUE->value => $info->getFirmwareVersion(),
		];

		if ($info->getStatusLed() !== null) {
			$states[] = [
				Types\PropertyParameter::NAME->value => Types\Parameter::STATUS_LED->value,
				Types\PropertyParameter::VALUE->value => $info->getStatusLed(),
			];
		}

		$this->queue->append(
			$this->entityHelper->create(
				Queue\Messages\StoreParametersStates::class,
				[
					'connector' => $this->connector->getId(),
					'identifier' => $info->getId(),
					'parameters' => $states,
				],
			),
		);
	}

}
