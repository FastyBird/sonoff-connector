<?php declare(strict_types = 1);

/**
 * CloudDiscovery.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           19.05.23
 */

namespace FastyBird\Connector\Sonoff\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queries;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
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
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDevice implements Queue\Consumer
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
		protected readonly ApplicationHelpers\Database $databaseHelper,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreDevice) {
			return false;
		}

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byIdentifier($message->getId());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\Device::class);

		if ($device === null) {
			$connector = $this->connectorsRepository->find(
				$message->getConnector(),
				Entities\Connectors\Connector::class,
			);

			if ($connector === null) {
				return true;
			}

			$device = $this->databaseHelper->transaction(
				function () use ($message, $connector): Entities\Devices\Device {
					$device = $this->devicesManager->create(Utils\ArrayHash::from([
						'entity' => Entities\Devices\Device::class,
						'connector' => $connector,
						'identifier' => $message->getId(),
						'name' => $message->getName(),
						'description' => $message->getDescription(),
					]));
					assert($device instanceof Entities\Devices\Device);

					return $device;
				},
			);

			$this->logger->info(
				'Device was created',
				[
					'source' => MetadataTypes\Sources\Connector::SONOFF->value,
					'type' => 'store-device-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
						'identifier' => $message->getId(),
						'address' => $message->getIpAddress(),
						'name' => $message->getName(),
					],
				],
			);
		}

		$this->setDeviceProperty(
			$device->getId(),
			$message->getApiKey(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::API_KEY,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::API_KEY->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getDeviceKey(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::DEVICE_KEY,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::DEVICE_KEY->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getUiid(),
			MetadataTypes\DataType::UCHAR,
			Types\DevicePropertyIdentifier::UIID,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::UIID->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getBrandName(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::BRAND_NAME,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::BRAND_NAME->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getBrandLogo(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::BRAND_LOGO,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::BRAND_LOGO->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getProductModel(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::PRODUCT_MODEL,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::PRODUCT_MODEL->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getModel(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::HARDWARE_MODEL,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::HARDWARE_MODEL->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getMac(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getIpAddress(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::IP_ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::IP_ADDRESS->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getDomain(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::ADDRESS->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getPort(),
			MetadataTypes\DataType::UINT,
			Types\DevicePropertyIdentifier::PORT,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::PORT->value),
		);

		foreach ($message->getParameters() as $parameter) {
			if ($parameter->getType() === Types\ParameterType::DEVICE) {
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
							'source' => MetadataTypes\Sources\Connector::SONOFF->value,
							'type' => 'store-device-message-consumer',
							'connector' => [
								'id' => $message->getConnector()->toString(),
							],
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
							'source' => MetadataTypes\Sources\Connector::SONOFF->value,
							'type' => 'store-device-message-consumer',
							'connector' => [
								'id' => $message->getConnector()->toString(),
							],
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

		$this->databaseHelper->transaction(function () use ($message, $device): bool {
			foreach ($message->getParameters() as $parameter) {
				if ($parameter->getType() === Types\ParameterType::CHANNEL) {
					$findChannelQuery = new Queries\Entities\FindChannels();
					$findChannelQuery->byIdentifier($parameter->getGroup());
					$findChannelQuery->forDevice($device);

					$channel = $this->channelsRepository->findOneBy(
						$findChannelQuery,
						Entities\Channels\Channel::class,
					);

					if ($channel === null) {
						$channel = $this->channelsManager->create(Utils\ArrayHash::from([
							'entity' => Entities\Channels\Channel::class,
							'device' => $device,
							'identifier' => $parameter->getGroup(),
						]));

						$this->logger->debug(
							'Device channel was created',
							[
								'source' => MetadataTypes\Sources\Connector::SONOFF->value,
								'type' => 'store-device-message-consumer',
								'connector' => [
									'id' => $message->getConnector()->toString(),
								],
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
				'source' => MetadataTypes\Sources\Connector::SONOFF->value,
				'type' => 'store-device-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

}
