<?php declare(strict_types = 1);

/**
 * Periodic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           14.05.23
 */

namespace FastyBird\Connector\Sonoff\Writers;

use DateTimeInterface;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use React\EventLoop;
use function array_key_exists;
use function in_array;

/**
 * Periodic properties writer
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Periodic implements Writer
{

	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 5.0;

	private const HANDLER_DEBOUNCE_INTERVAL = 2_500.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const HANDLER_PENDING_DELAY = 2_000.0;

	/** @var array<string, MetadataDocuments\DevicesModule\Device>  */
	private array $devices = [];
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	/** @var array<string, array<string, MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty>>  */
	private array $properties = [];

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, DateTimeInterface> */
	private array $processedProperties = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		protected readonly MetadataDocuments\DevicesModule\Connector $connector,
		protected readonly Helpers\Entity $entityHelper,
		protected readonly Queue\Queue $queue,
		protected readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		protected readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesUtilities\DevicePropertiesStates $devicePropertiesStatesManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStatesManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function connect(): void
	{
		$this->processedDevices = [];
		$this->processedProperties = [];

		$findDevicesQuery = new DevicesQueries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($this->connector);
		$findDevicesQuery->byType(Entities\SonoffDevice::TYPE);

		foreach ($this->devicesConfigurationRepository->findAllBy($findDevicesQuery) as $device) {
			$this->devices[$device->getId()->toString()] = $device;

			if (!array_key_exists($device->getId()->toString(), $this->properties)) {
				$this->properties[$device->getId()->toString()] = [];
			}

			$findDevicePropertiesQuery = new DevicesQueries\Configuration\FindDeviceProperties();
			$findDevicePropertiesQuery->forDevice($device);
			$findDevicePropertiesQuery->settable(true);

			$properties = $this->devicesPropertiesConfigurationRepository->findAllBy($findDevicePropertiesQuery);

			foreach ($properties as $property) {
				if (
					$property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty
					|| $property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty
				) {
					$this->properties[$device->getId()->toString()][$property->getId()->toString()] = $property;
				}
			}

			$findChannelsQuery = new DevicesQueries\Configuration\FindChannels();
			$findChannelsQuery->forDevice($device);
			$findChannelsQuery->byType(Entities\SonoffChannel::TYPE);

			$channels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

			foreach ($channels as $channel) {
				$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
				$findChannelPropertiesQuery->forChannel($channel);
				$findChannelPropertiesQuery->settable(true);

				$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
					$findChannelPropertiesQuery,
					MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
				);

				foreach ($properties as $property) {
					$this->properties[$device->getId()->toString()][$property->getId()->toString()] = $property;
				}
			}
		}

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			function (): void {
				$this->registerLoopHandler();
			},
		);
	}

	public function disconnect(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function handleCommunication(): void
	{
		foreach ($this->devices as $device) {
			if (!in_array($device->getId()->toString(), $this->processedDevices, true)) {
				$this->processedDevices[] = $device->getId()->toString();

				if ($this->writeChannelsProperty($device)) {
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
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function writeChannelsProperty(MetadataDocuments\DevicesModule\Device $device): bool
	{
		$now = $this->dateTimeFactory->getNow();

		foreach ($this->properties[$device->getId()->toString()] as $property) {
			$debounce = array_key_exists($property->getId()->toString(), $this->processedProperties)
				? $this->processedProperties[$property->getId()->toString()]
				: false;

			if (
				$debounce !== false
				&& (float) $now->format('Uv') - (float) $debounce->format('Uv') < self::HANDLER_DEBOUNCE_INTERVAL
			) {
				continue;
			}

			$this->processedProperties[$property->getId()->toString()] = $now;

			$state = $property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty
			|| $property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty
				? $this->devicePropertiesStatesManager->getValue($property)
				: $this->channelPropertiesStatesManager->getValue($property);

			if ($state === null) {
				continue;
			}

			$expectedValue = MetadataUtilities\ValueHelper::flattenValue($state->getExpectedValue());

			if ($expectedValue === null) {
				continue;
			}

			$pending = $state->getPending();

			if (
				$pending === true
				|| (
					$pending instanceof DateTimeInterface
					&& (float) $now->format('Uv') - (float) $pending->format('Uv') > self::HANDLER_PENDING_DELAY
				)
			) {
				if (
					$property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty
					|| $property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty
				) {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\WriteDevicePropertyState::class,
							[
								'connector' => $device->getConnector(),
								'device' => $device->getId(),
								'property' => $property->getId(),
							],
						),
					);
				} else {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\WriteChannelPropertyState::class,
							[
								'connector' => $device->getConnector(),
								'device' => $device->getId(),
								'channel' => $property->getChannel(),
								'property' => $property->getId(),
							],
						),
					);
				}

				return true;
			}
		}

		return false;
	}

	private function registerLoopHandler(): void
	{
		$this->handlerTimer = $this->eventLoop->addTimer(
			self::HANDLER_PROCESSING_INTERVAL,
			function (): void {
				$this->handleCommunication();
			},
		);
	}

}
