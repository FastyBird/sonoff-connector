<?php declare(strict_types = 1);

/**
 * StoreDeviceConnectionState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           04.09.22
 */

namespace FastyBird\Connector\Sonoff\Queue\Consumers;

use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Queries;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;

/**
 * Store device connection state message consumer
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceConnectionState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Sonoff\Logger $logger,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DevicesUtilities\DevicePropertiesStates $devicePropertiesStateManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStateManager,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreDeviceConnectionState) {
			return false;
		}

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\SonoffDevice::class);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'store-device-connection-state-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'identifier' => $entity->getIdentifier(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		// Check device state...
		if (
			!$this->deviceConnectionManager->getState($device)->equals($entity->getState())
		) {
			// ... and if it is not ready, set it to ready
			$this->deviceConnectionManager->setState(
				$device,
				$entity->getState(),
			);

			if (
				$entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_DISCONNECTED)
				|| $entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_LOST)
				|| $entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_ALERT)
				|| $entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_UNKNOWN)
			) {
				$findDevicePropertiesQuery = new DevicesQueries\Entities\FindDeviceDynamicProperties();
				$findDevicePropertiesQuery->forDevice($device);

				foreach ($this->devicesPropertiesRepository->findAllBy(
					$findDevicePropertiesQuery,
					DevicesEntities\Devices\Properties\Dynamic::class,
				) as $property) {
					$this->devicePropertiesStateManager->setValidState($property, false);
				}

				$findChannelsQuery = new DevicesQueries\Entities\FindChannels();
				$findChannelsQuery->forDevice($device);

				$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

				foreach ($channels as $channel) {
					$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					foreach ($this->channelsPropertiesRepository->findAllBy(
						$findChannelPropertiesQuery,
						DevicesEntities\Channels\Properties\Dynamic::class,
					) as $property) {
						$this->channelPropertiesStateManager->setValidState($property, false);
					}
				}
			}

			$findChildrenDevicesQuery = new Queries\Entities\FindDevices();
			$findChildrenDevicesQuery->forParent($device);

			$children = $this->devicesRepository->findAllBy($findChildrenDevicesQuery, Entities\SonoffDevice::class);

			foreach ($children as $child) {
				$this->deviceConnectionManager->setState(
					$child,
					$entity->getState(),
				);

				if (
					$entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_DISCONNECTED)
					|| $entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_LOST)
					|| $entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_ALERT)
					|| $entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_UNKNOWN)
				) {
					$findDevicePropertiesQuery = new DevicesQueries\Entities\FindDeviceDynamicProperties();
					$findDevicePropertiesQuery->forDevice($child);

					foreach ($this->devicesPropertiesRepository->findAllBy(
						$findDevicePropertiesQuery,
						DevicesEntities\Devices\Properties\Dynamic::class,
					) as $property) {
						$this->devicePropertiesStateManager->setValidState($property, false);
					}

					$findChannelsQuery = new DevicesQueries\Entities\FindChannels();
					$findChannelsQuery->forDevice($child);

					$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

					foreach ($channels as $channel) {
						$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
						$findChannelPropertiesQuery->forChannel($channel);

						foreach ($this->channelsPropertiesRepository->findAllBy(
							$findChannelPropertiesQuery,
							DevicesEntities\Channels\Properties\Dynamic::class,
						) as $property) {
							$this->channelPropertiesStateManager->setValidState($property, false);
						}
					}
				}
			}
		}

		$this->logger->debug(
			'Consumed device connection status message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
				'type' => 'store-device-connection-state-message-consumer',
				'connector' => [
					'id' => $entity->getConnector()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
