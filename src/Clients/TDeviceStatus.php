<?php declare(strict_types = 1);

/**
 * Cloud.php
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

use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette\Utils;
use Psr\Log;
use function assert;
use function is_scalar;
use function strval;

/**
 * Device ip address consumer trait
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read DevicesModels\Devices\DevicesRepository $devicesRepository
 * @property-read DevicesModels\Channels\ChannelsRepository $channelsRepository
 * @property-read DevicesModels\Devices\Properties\PropertiesRepository $devicePropertiesRepository
 * @property-read DevicesModels\Channels\Properties\PropertiesRepository $channelPropertiesRepository
 * @property-read Log\LoggerInterface $logger
 */
trait TDeviceStatus
{

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function handleDeviceStatus(
		Entities\API\DeviceStatus|Entities\API\DeviceUpdated $deviceStatus,
	): void
	{
		if ($deviceStatus->getParams() === null) {
			return;
		}

		$statuses = [];

		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byIdentifier($deviceStatus->getDeviceId());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\SonoffDevice::class);

		if ($device === null) {
			return;
		}

		foreach ((array) $deviceStatus->getParams() as $identifier => $parameter) {
			if ($parameter instanceof Utils\ArrayHash) {
				foreach ((array) $parameter as $index => $group) {
					if (!$group instanceof Utils\ArrayHash) {
						continue;
					}

					foreach ($group as $subIdentifier => $value) {
						$channelIdentifier = API\Transformer::groupToChannelIdentifier(
							strval($identifier),
							strval($subIdentifier),
						);

						if ($channelIdentifier === null) {
							$this->logger->debug(
								'Channel identifier could not be determined',
								[
									'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
									'type' => 'device-status',
									'device' => [
										'id' => $deviceStatus->getDeviceId(),
									],
									'parameter' => [
										'identifier' => $identifier,
										'sub_identifier' => $subIdentifier,
									],
								],
							);

							continue;
						}

						$findChannelQuery = new DevicesQueries\FindChannels();
						$findChannelQuery->forDevice($device);
						$findChannelQuery->byIdentifier($channelIdentifier . '_' . $index);

						$channel = $this->channelsRepository->findOneBy($findChannelQuery);

						if ($channel === null) {
							continue;
						}

						assert(is_scalar($value));

						$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
						$findChannelPropertyQuery->forChannel($channel);
						$findChannelPropertyQuery->byIdentifier(
							API\Transformer::deviceParameterNameToProperty($subIdentifier),
						);

						$property = $this->channelPropertiesRepository->findOneBy($findChannelPropertyQuery);

						if ($property !== null) {
							$statuses[] = new Entities\Messages\ChannelParameterStatus(
								$property->getIdentifier(),
								$channel->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$value,
								),
							);

							continue;
						}

						$this->logger->debug(
							'Unsupported parameter',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
								'type' => 'device-status',
								'device' => [
									'id' => $deviceStatus->getDeviceId(),
								],
								'parameter' => [
									'identifier' => $identifier,
									'sub_identifier' => $subIdentifier,
								],
							],
						);
					}
				}
			} elseif (is_scalar($parameter)) {
				if (
					API\Transformer::deviceParameterNameToProperty(
						$identifier,
					) !== Sonoff\Types\DevicePropertyIdentifier::IDENTIFIER_STATE
				) {
					$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
					$findDevicePropertyQuery->forDevice($device);
					$findDevicePropertyQuery->byIdentifier(API\Transformer::deviceParameterNameToProperty($identifier));

					$property = $this->devicePropertiesRepository->findOneBy($findDevicePropertyQuery);

					if ($property !== null) {
						$statuses[] = new Entities\Messages\DeviceParameterStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$parameter,
							),
						);

						continue;
					}
				}

				$findChannelQuery = new DevicesQueries\FindChannels();
				$findChannelQuery->forDevice($device);
				$findChannelQuery->byIdentifier(Sonoff\Constants::CHANNEL_NAME);

				$channel = $this->channelsRepository->findOneBy($findChannelQuery);

				if ($channel === null) {
					continue;
				}

				$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
				$findChannelPropertyQuery->forChannel($channel);
				$findChannelPropertyQuery->byIdentifier(API\Transformer::deviceParameterNameToProperty($identifier));

				$property = $this->channelPropertiesRepository->findOneBy($findChannelPropertyQuery);

				if ($property !== null) {
					$statuses[] = new Entities\Messages\ChannelParameterStatus(
						$property->getIdentifier(),
						$channel->getIdentifier(),
						API\Transformer::transformValueFromDevice(
							$property->getDataType(),
							$property->getFormat(),
							$parameter,
						),
					);

					continue;
				}

				$this->logger->debug(
					'Unsupported parameter',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'device-status',
						'device' => [
							'id' => $deviceStatus->getDeviceId(),
						],
						'parameter' => [
							'identifier' => $identifier,
						],
					],
				);
			} else {
				$this->logger->debug(
					'Unknown parameter',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'device-status',
						'device' => [
							'id' => $deviceStatus->getDeviceId(),
						],
						'parameter' => $parameter,
					],
				);
			}
		}

		if ($statuses === []) {
			return;
		}

		$this->consumer->append(new Entities\Messages\DeviceStatus(
			$this->connector->getId(),
			$deviceStatus->getDeviceId(),
			$statuses,
		));
	}

}
