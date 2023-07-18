<?php declare(strict_types = 1);

/**
 * CloudDiscovery.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           19.05.23
 */

namespace FastyBird\Connector\Sonoff\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\Connector\Sonoff\Consumers\Consumer;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use Psr\Log;
use function assert;

/**
 * Device discovery message consumer
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicesDiscovery implements Consumer
{

	use Nette\SmartObject;
	use ConsumeDeviceProperty;

	public function __construct(
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesUtilities\Database $databaseHelper,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\DiscoveredDevice) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getId());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\SonoffDevice::class);

		if ($device === null) {
			$findConnectorQuery = new DevicesQueries\FindConnectors();
			$findConnectorQuery->byId($entity->getConnector());

			$connector = $this->connectorsRepository->findOneBy(
				$findConnectorQuery,
				Entities\SonoffConnector::class,
			);
			assert($connector instanceof Entities\SonoffConnector || $connector === null);

			if ($connector === null) {
				return true;
			}

			$device = $this->databaseHelper->transaction(
				function () use ($entity, $connector): Entities\SonoffDevice {
					$device = $this->devicesManager->create(Utils\ArrayHash::from([
						'entity' => Entities\SonoffDevice::class,
						'connector' => $connector,
						'identifier' => $entity->getId(),
						'name' => $entity->getName(),
						'description' => $entity->getDescription(),
					]));
					assert($device instanceof Entities\SonoffDevice);

					return $device;
				},
			);

			$this->logger->info(
				'Creating new device',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'devices-discovery-message-consumer',
					'device' => [
						'id' => $device->getPlainId(),
						'identifier' => $entity->getId(),
						'address' => $entity->getIpAddress(),
						'name' => $entity->getName(),
					],
				],
			);
		} else {
			$device = $this->databaseHelper->transaction(
				function () use ($entity, $device): Entities\SonoffDevice {
					$device = $this->devicesManager->update($device, Utils\ArrayHash::from([
						'name' => $entity->getName(),
						'description' => $entity->getDescription(),
					]));
					assert($device instanceof Entities\SonoffDevice);

					return $device;
				},
			);

			$this->logger->debug(
				'Device was updated',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'devices-discovery-message-consumer',
					'device' => [
						'id' => $device->getPlainId(),
					],
				],
			);
		}

		$this->setDeviceProperty(
			$device->getId(),
			$entity->getApiKey(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_API_KEY,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_API_KEY),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getDeviceKey(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_DEVICE_KEY,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_DEVICE_KEY),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getUiid(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
			Types\DevicePropertyIdentifier::IDENTIFIER_UIID,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_UIID),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getBrandName(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_BRAND_NAME,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_BRAND_NAME),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getBrandLogo(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_BRAND_LOGO,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_BRAND_LOGO),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getProductModel(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_PRODUCT_MODEL,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_PRODUCT_MODEL),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getModel(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MODEL,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MODEL),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getMac(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getIpAddress(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getDomain(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_ADDRESS,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_ADDRESS),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getPort(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
			Types\DevicePropertyIdentifier::IDENTIFIER_PORT,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_PORT),
		);

		foreach ($entity->getProperties() as $parameter) {
			if ($parameter->getType()->equalsValue(Types\DeviceParameterType::TYPE_DEVICE)) {
				$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($device);
				$findDevicePropertyQuery->byIdentifier($parameter->getIdentifier());

				$property = $this->propertiesRepository->findOneBy($findDevicePropertyQuery);

				if (
					$property !== null
					&& !$property instanceof DevicesEntities\Devices\Properties\Dynamic
				) {
					$this->databaseHelper->transaction(function () use ($property): void {
						$this->propertiesManager->delete($property);
					});

					$property = null;
				}

				if ($property === null) {
					$property = $this->databaseHelper->transaction(
						fn (): DevicesEntities\Devices\Properties\Property => $this->propertiesManager->create(
							Utils\ArrayHash::from([
								'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
								'device' => $device,
								'identifier' => $parameter->getIdentifier(),
								'name' => $parameter->getName(),
								'dataType' => $parameter->getDataType(),
								'settable' => $parameter->isSettable(),
								'queryable' => $parameter->isQueryable(),
								'format' => $parameter->getFormat(),
							]),
						),
					);

					$this->logger->debug(
						'Device dynamic property was created',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
							'type' => 'message-consumer',
							'device' => [
								'id' => $device->getPlainId(),
							],
							'property' => [
								'id' => $property->getPlainId(),
								'identifier' => $parameter->getIdentifier(),
							],
						],
					);

				} else {
					$property = $this->databaseHelper->transaction(
						fn (): DevicesEntities\Devices\Properties\Property => $this->propertiesManager->update(
							$property,
							Utils\ArrayHash::from([
								'name' => $parameter->getName(),
								'dataType' => $parameter->getDataType(),
								'settable' => $parameter->isSettable(),
								'queryable' => $parameter->isQueryable(),
								'format' => $parameter->getFormat(),
							]),
						),
					);

					$this->logger->debug(
						'Device dynamic property was updated',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
							'type' => 'message-consumer',
							'device' => [
								'id' => $device->getPlainId(),
							],
							'property' => [
								'id' => $property->getPlainId(),
								'identifier' => $parameter->getIdentifier(),
							],
						],
					);
				}
			}
		}

		$this->databaseHelper->transaction(function () use ($entity, $device): bool {
			foreach ($entity->getProperties() as $parameter) {
				if ($parameter->getType()->equalsValue(Types\DeviceParameterType::TYPE_CHANNEL)) {
					$findChannelQuery = new DevicesQueries\FindChannels();
					$findChannelQuery->byIdentifier($parameter->getGroup());
					$findChannelQuery->forDevice($device);

					$channel = $this->channelsRepository->findOneBy($findChannelQuery);

					if ($channel === null) {
						$channel = $this->channelsManager->create(Utils\ArrayHash::from([
							'device' => $device,
							'identifier' => $parameter->getGroup(),
						]));

						$this->logger->debug(
							'Creating new device channel',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
								'type' => 'devices-discovery-message-consumer',
								'device' => [
									'id' => $device->getPlainId(),
								],
								'channel' => [
									'id' => $channel->getPlainId(),
								],
							],
						);
					}

					$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
					$findChannelPropertyQuery->forChannel($channel);
					$findChannelPropertyQuery->byIdentifier($parameter->getIdentifier());

					$property = $this->channelPropertiesRepository->findOneBy($findChannelPropertyQuery);

					if ($property === null) {
						$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
							'channel' => $channel,
							'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
							'identifier' => $parameter->getIdentifier(),
							'name' => $parameter->getName(),
							'dataType' => $parameter->getDataType(),
							'format' => $parameter->getFormat(),
							'queryable' => $parameter->isQueryable(),
							'settable' => $parameter->isSettable(),
						]));

					} else {
						$this->channelsPropertiesManager->update($property, Utils\ArrayHash::from([
							'dataType' => $parameter->getDataType(),
							'format' => $parameter->getFormat(),
							'queryable' => $parameter->isQueryable(),
							'settable' => $parameter->isSettable(),
						]));
					}
				}
			}

			return true;
		});

		$this->logger->debug(
			'Consumed device found message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
				'type' => 'devices-discovery-message-consumer',
				'device' => [
					'id' => $device->getPlainId(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
