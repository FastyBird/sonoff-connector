<?php declare(strict_types = 1);

/**
 * Status.php
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

use Doctrine\DBAL;
use FastyBird\Connector\Sonoff\Consumers\Consumer;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use Psr\Log;

/**
 * Device status message consumer
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Status implements Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesUtilities\Database $databaseHelper,
		private readonly Helpers\Property $propertyStateHelper,
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
		if (!$entity instanceof Entities\Messages\DeviceStatus) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->startWithIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\SonoffDevice::class);

		if ($device === null) {
			return true;
		}

		foreach ($entity->getParameters() as $parameter) {
			if ($parameter instanceof Entities\Messages\DeviceParameterStatus) {
				$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($device);
				$findDevicePropertyQuery->byIdentifier($parameter->getIdentifier());

				$property = $this->devicePropertiesRepository->findOneBy($findDevicePropertyQuery);

				if ($property instanceof DevicesEntities\Devices\Properties\Dynamic) {
					$this->propertyStateHelper->setValue($property, Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_KEY => $parameter->getValue(),
						DevicesStates\Property::VALID_KEY => true,
					]));
				} elseif ($property instanceof DevicesEntities\Devices\Properties\Variable) {
					$this->databaseHelper->transaction(
						function () use ($property, $parameter): void {
							$this->devicesPropertiesManager->update(
								$property,
								Utils\ArrayHash::from([
									'value' => $parameter->getValue(),
								]),
							);
						},
					);
				}
			} else {
				$findChannelQuery = new DevicesQueries\FindChannels();
				$findChannelQuery->forDevice($device);
				$findChannelQuery->byIdentifier($parameter->getChannel());

				$channel = $this->channelsRepository->findOneBy($findChannelQuery);

				if ($channel !== null) {
					$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
					$findChannelPropertyQuery->forChannel($channel);
					$findChannelPropertyQuery->byIdentifier($parameter->getIdentifier());

					$property = $this->channelPropertiesRepository->findOneBy($findChannelPropertyQuery);

					if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
						$this->propertyStateHelper->setValue($property, Utils\ArrayHash::from([
							DevicesStates\Property::ACTUAL_VALUE_KEY => $parameter->getValue(),
							DevicesStates\Property::VALID_KEY => true,
						]));
					} elseif ($property instanceof DevicesEntities\Channels\Properties\Variable) {
						$this->databaseHelper->transaction(
							function () use ($property, $parameter): void {
								$this->channelsPropertiesManager->update(
									$property,
									Utils\ArrayHash::from([
										'value' => $parameter->getValue(),
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
				'type' => 'status-message-consumer',
				'device' => [
					'id' => $device->getPlainId(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
