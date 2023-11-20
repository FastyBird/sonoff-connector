<?php declare(strict_types = 1);

/**
 * StoreParametersStates.php
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

namespace FastyBird\Connector\Sonoff\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Queue\Consumer;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;

/**
 * Device state message consumer
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreParametersStates implements Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Sonoff\Logger $logger,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesUtilities\DevicePropertiesStates $devicePropertiesStateManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStateManager,
		private readonly DevicesUtilities\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreParametersStates) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\Entities\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->startWithIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\SonoffDevice::class);

		if ($device === null) {
			return true;
		}

		foreach ($entity->getParameters() as $parameter) {
			if ($parameter instanceof Entities\Messages\States\DeviceParameterState) {
				$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($device);
				$findDevicePropertyQuery->byIdentifier($parameter->getName());

				$property = $this->devicePropertiesRepository->findOneBy($findDevicePropertyQuery);

				if ($property instanceof DevicesEntities\Devices\Properties\Dynamic) {
					$this->devicePropertiesStateManager->setValue($property, Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_FIELD => MetadataUtilities\ValueHelper::transformValueFromDevice(
							$property->getDataType(),
							$property->getFormat(),
							$parameter->getValue(),
						),
						DevicesStates\Property::VALID_FIELD => true,
					]));
				} elseif ($property instanceof DevicesEntities\Devices\Properties\Variable) {
					$this->databaseHelper->transaction(
						function () use ($property, $parameter): void {
							$this->devicesPropertiesManager->update(
								$property,
								Utils\ArrayHash::from([
									'value' => MetadataUtilities\ValueHelper::transformValueFromDevice(
										$property->getDataType(),
										$property->getFormat(),
										$parameter->getValue(),
									),
								]),
							);
						},
					);
				}
			} elseif ($parameter instanceof Entities\Messages\States\ChannelParameterState) {
				$findChannelQuery = new DevicesQueries\Entities\FindChannels();
				$findChannelQuery->forDevice($device);
				$findChannelQuery->byIdentifier($parameter->getGroup());

				$channel = $this->channelsRepository->findOneBy($findChannelQuery);

				if ($channel !== null) {
					$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
					$findChannelPropertyQuery->forChannel($channel);
					$findChannelPropertyQuery->byIdentifier($parameter->getName());

					$property = $this->channelPropertiesRepository->findOneBy($findChannelPropertyQuery);

					if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
						$this->channelPropertiesStateManager->setValue($property, Utils\ArrayHash::from([
							DevicesStates\Property::ACTUAL_VALUE_FIELD => MetadataUtilities\ValueHelper::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$parameter->getValue(),
							),
							DevicesStates\Property::VALID_FIELD => true,
						]));
					} elseif ($property instanceof DevicesEntities\Channels\Properties\Variable) {
						$this->databaseHelper->transaction(
							function () use ($property, $parameter): void {
								$this->channelsPropertiesManager->update(
									$property,
									Utils\ArrayHash::from([
										'value' => MetadataUtilities\ValueHelper::transformValueFromDevice(
											$property->getDataType(),
											$property->getFormat(),
											$parameter->getValue(),
										),
									]),
								);
							},
						);
					}
				}
			}
		}

		$this->logger->debug(
			'Consumed device status message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
				'type' => 'status-parameters-states-message-consumer',
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
