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

namespace FastyBird\Connector\Sonoff\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queries;
use FastyBird\Connector\Sonoff\Queue\Consumer;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use function assert;

/**
 * Device discovery message consumer
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDevice implements Consumer
{

	use Nette\SmartObject;
	use DeviceProperty;
	use ChannelProperty;

	public function __construct(
		protected readonly Sonoff\Logger $logger,
		protected readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		protected readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		protected readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		protected readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		protected readonly DevicesUtilities\Database $databaseHelper,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreDevice) {
			return false;
		}

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getId());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\SonoffDevice::class);

		if ($device === null) {
			$findConnectorQuery = new Queries\Entities\FindConnectors();
			$findConnectorQuery->byId($entity->getConnector());

			$connector = $this->connectorsRepository->findOneBy(
				$findConnectorQuery,
				Entities\SonoffConnector::class,
			);

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
				'Device was created',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'store-device-message-consumer',
					'device' => [
						'id' => $device->getId()->toString(),
						'identifier' => $entity->getId(),
						'address' => $entity->getIpAddress(),
						'name' => $entity->getName(),
					],
				],
			);
		}

		$this->setDeviceProperty(
			$device->getId(),
			$entity->getApiKey(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::API_KEY,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::API_KEY),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getDeviceKey(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::DEVICE_KEY,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::DEVICE_KEY),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getUiid(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
			Types\DevicePropertyIdentifier::UIID,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::UIID),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getBrandName(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::BRAND_NAME,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::BRAND_NAME),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getBrandLogo(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::BRAND_LOGO,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::BRAND_LOGO),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getProductModel(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::PRODUCT_MODEL,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::PRODUCT_MODEL),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getModel(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::HARDWARE_MODEL,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::HARDWARE_MODEL),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getMac(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getIpAddress(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IP_ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::IP_ADDRESS),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getDomain(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::ADDRESS),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getPort(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
			Types\DevicePropertyIdentifier::PORT,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::PORT),
		);

		foreach ($entity->getParameters() as $parameter) {
			if ($parameter->getType()->equalsValue(Types\ParameterType::DEVICE)) {
				$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($device);
				$findDevicePropertyQuery->byIdentifier($parameter->getIdentifier());

				$property = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

				if (
					$property !== null
					&& !$property instanceof DevicesEntities\Devices\Properties\Dynamic
				) {
					$this->databaseHelper->transaction(function () use ($property): void {
						$this->devicesPropertiesManager->delete($property);
					});

					$property = null;
				}

				if ($property === null) {
					$property = $this->databaseHelper->transaction(
						fn (): DevicesEntities\Devices\Properties\Property => $this->devicesPropertiesManager->create(
							Utils\ArrayHash::from([
								'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
								'device' => $device,
								'identifier' => Helpers\Transformer::deviceParameterToProperty(
									$parameter->getIdentifier(),
								),
								'name' => $parameter->getName(),
								'dataType' => $parameter->getDataType(),
								'settable' => $parameter->isSettable(),
								'queryable' => $parameter->isQueryable(),
								'format' => $parameter->getFormat(),
								'scale' => $parameter->getScale(),
							]),
						),
					);

					$this->logger->debug(
						'Device dynamic property was created',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
							'type' => 'store-device-message-consumer',
							'device' => [
								'id' => $device->getId()->toString(),
							],
							'property' => [
								'id' => $property->getId()->toString(),
								'identifier' => $parameter->getIdentifier(),
							],
						],
					);

				} else {
					$property = $this->databaseHelper->transaction(
						fn (): DevicesEntities\Devices\Properties\Property => $this->devicesPropertiesManager->update(
							$property,
							Utils\ArrayHash::from([
								'name' => $parameter->getName(),
								'dataType' => $parameter->getDataType(),
								'settable' => $parameter->isSettable(),
								'queryable' => $parameter->isQueryable(),
								'format' => $parameter->getFormat(),
								'scale' => $parameter->getScale(),
							]),
						),
					);

					$this->logger->debug(
						'Device dynamic property was updated',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
							'type' => 'store-device-message-consumer',
							'device' => [
								'id' => $device->getId()->toString(),
							],
							'property' => [
								'id' => $property->getId()->toString(),
								'identifier' => $parameter->getIdentifier(),
							],
						],
					);
				}
			}
		}

		$this->databaseHelper->transaction(function () use ($entity, $device): bool {
			foreach ($entity->getParameters() as $parameter) {
				if ($parameter->getType()->equalsValue(Types\ParameterType::CHANNEL)) {
					$findChannelQuery = new Queries\Entities\FindChannels();
					$findChannelQuery->byIdentifier($parameter->getGroup());
					$findChannelQuery->forDevice($device);

					$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\SonoffChannel::class);

					if ($channel === null) {
						$channel = $this->channelsManager->create(Utils\ArrayHash::from([
							'entity' => Entities\SonoffChannel::class,
							'device' => $device,
							'identifier' => $parameter->getGroup(),
						]));

						$this->logger->debug(
							'Device channel was created',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
								'type' => 'store-device-message-consumer',
								'device' => [
									'id' => $device->getId()->toString(),
								],
								'channel' => [
									'id' => $channel->getId()->toString(),
								],
							],
						);
					}

					$this->setChannelProperty(
						DevicesEntities\Channels\Properties\Dynamic::class,
						$channel->getId(),
						null,
						$parameter->getDataType(),
						$parameter->getIdentifier(),
						$parameter->getName(),
						$parameter->getFormat(),
						null,
						null,
						$parameter->getScale(),
						$parameter->isSettable(),
						$parameter->isQueryable(),
					);
				}
			}

			return true;
		});

		$this->logger->debug(
			'Consumed store device message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
				'type' => 'store-device-message-consumer',
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
