<?php declare(strict_types = 1);

/**
 * State.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           27.05.23
 */

namespace FastyBird\Connector\Sonoff\Consumers\Messages;

use FastyBird\Connector\Sonoff\Consumers\Consumer;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Psr\Log;

/**
 * Device state message consumer
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class State implements Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Helpers\Property $propertyStateHelper,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
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
		if (!$entity instanceof Entities\Messages\DeviceState) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\SonoffDevice::class);

		if ($device === null) {
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
				|| $entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_STOPPED)
				|| $entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_LOST)
				|| $entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_UNKNOWN)
			) {
				$findDevicePropertiesQuery = new DevicesQueries\FindDeviceProperties();
				$findDevicePropertiesQuery->forDevice($device);

				foreach ($this->devicePropertiesRepository->findAllBy($findDevicePropertiesQuery) as $property) {
					if (!$property instanceof DevicesEntities\Devices\Properties\Dynamic) {
						continue;
					}

					$this->propertyStateHelper->setValue(
						$property,
						Nette\Utils\ArrayHash::from([
							DevicesStates\Property::VALID_KEY => false,
						]),
					);
				}

				$findChannelsQuery = new DevicesQueries\FindChannels();
				$findChannelsQuery->forDevice($device);

				$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

				foreach ($channels as $channel) {
					foreach ($channel->getProperties() as $property) {
						if (!$property instanceof DevicesEntities\Channels\Properties\Dynamic) {
							continue;
						}

						$this->propertyStateHelper->setValue(
							$property,
							Nette\Utils\ArrayHash::from([
								DevicesStates\Property::VALID_KEY => false,
							]),
						);
					}
				}
			}

			$findChildrenDevicesQuery = new DevicesQueries\FindDevices();
			$findChildrenDevicesQuery->forParent($device);

			$children = $this->devicesRepository->findAllBy($findChildrenDevicesQuery);

			foreach ($children as $child) {
				$this->deviceConnectionManager->setState(
					$child,
					$entity->getState(),
				);

				if ($entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_DISCONNECTED)) {
					foreach ($child->getProperties() as $property) {
						if (!$property instanceof DevicesEntities\Devices\Properties\Dynamic) {
							continue;
						}

						$this->propertyStateHelper->setValue(
							$property,
							Nette\Utils\ArrayHash::from([
								DevicesStates\Property::VALID_KEY => false,
							]),
						);
					}

					foreach ($child->getChannels() as $channel) {
						foreach ($channel->getProperties() as $property) {
							if (!$property instanceof DevicesEntities\Channels\Properties\Dynamic) {
								continue;
							}

							$this->propertyStateHelper->setValue(
								$property,
								Nette\Utils\ArrayHash::from([
									DevicesStates\Property::VALID_KEY => false,
								]),
							);
						}
					}
				}
			}
		}

		$this->logger->debug(
			'Consumed device state message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
				'type' => 'state-message-consumer',
				'device' => [
					'id' => $device->getPlainId(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
