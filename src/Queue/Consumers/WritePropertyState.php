<?php declare(strict_types = 1);

/**
 * WritePropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           17.08.23
 */

namespace FastyBird\Connector\Sonoff\Queue\Consumers;

use DateTimeInterface;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use React\Promise;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function array_merge;
use function intval;
use function preg_match;
use function strval;

/**
 * Write state to device message consumer
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WritePropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	/**
	 * @param DevicesModels\Configuration\Connectors\Repository<MetadataDocuments\DevicesModule\Connector> $connectorsConfigurationRepository
	 * @param DevicesModels\Configuration\Devices\Repository<MetadataDocuments\DevicesModule\Device> $devicesConfigurationRepository
	 * @param DevicesModels\Configuration\Devices\Properties\Repository<MetadataDocuments\DevicesModule\DeviceDynamicProperty> $devicesPropertiesConfigurationRepository
	 * @param DevicesModels\Configuration\Channels\Repository<MetadataDocuments\DevicesModule\Channel> $channelsConfigurationRepository
	 * @param DevicesModels\Configuration\Channels\Properties\Repository<MetadataDocuments\DevicesModule\ChannelDynamicProperty> $channelsPropertiesConfigurationRepository
	 */
	public function __construct(
		private readonly Queue\Queue $queue,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\Entity $entityHelper,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Helpers\Device $deviceHelper,
		private readonly Sonoff\Logger $logger,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesUtilities\DevicePropertiesStates $devicePropertiesStatesManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStatesManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (
			!$entity instanceof Entities\Messages\WriteDevicePropertyState
			&& !$entity instanceof Entities\Messages\WriteChannelPropertyState
		) {
			return false;
		}

		$now = $this->dateTimeFactory->getNow();

		$findConnectorQuery = new DevicesQueries\Configuration\FindConnectors();
		$findConnectorQuery->byId($entity->getConnector());

		$connector = $this->connectorsConfigurationRepository->findOneBy($findConnectorQuery);

		if ($connector === null) {
			$this->logger->error(
				'Connector could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'write-property-state-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byId($entity->getDevice());

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'write-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$channel = null;

		if ($entity instanceof Entities\Messages\WriteChannelPropertyState) {
			$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byId($entity->getChannel());

			$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

			if ($channel === null) {
				$this->logger->error(
					'Channel could not be loaded',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'write-property-state-message-consumer',
						'connector' => [
							'id' => $connector->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
						],
						'property' => [
							'id' => $entity->getProperty()->toString(),
						],
						'data' => $entity->toArray(),
					],
				);

				return true;
			}

			$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byId($entity->getProperty());

			$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
				$findChannelPropertyQuery,
				MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
			);

			if ($property === null) {
				$this->logger->error(
					'Channel property could not be loaded',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'write-property-state-message-consumer',
						'connector' => [
							'id' => $connector->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
						],
						'property' => [
							'id' => $entity->getProperty()->toString(),
						],
						'data' => $entity->toArray(),
					],
				);

				return true;
			}
		} else {
			$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceDynamicProperties();
			$findDevicePropertyQuery->forDevice($device);
			$findDevicePropertyQuery->byId($entity->getProperty());

			$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
				$findDevicePropertyQuery,
				MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
			);

			if ($property === null) {
				$this->logger->error(
					'Device property could not be loaded',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'write-property-state-message-consumer',
						'connector' => [
							'id' => $connector->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
						],
						'property' => [
							'id' => $entity->getProperty()->toString(),
						],
						'data' => $entity->toArray(),
					],
				);

				return true;
			}
		}

		if (!$property->isSettable()) {
			$this->logger->error(
				'Property is not writable',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'write-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'property' => [
						'id' => $property->getId()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$state = $property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
			? $this->channelPropertiesStatesManager->getValue($property)
			: $this->devicePropertiesStatesManager->getValue($property);

		if ($state === null) {
			return true;
		}

		$expectedValue = MetadataUtilities\ValueHelper::transformValueToDevice(
			$property->getDataType(),
			$property->getFormat(),
			$state->getExpectedValue(),
		);

		if ($expectedValue === null) {
			if ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
				$this->channelPropertiesStatesManager->setValue(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
						DevicesStates\Property::PENDING_FIELD => false,
					]),
				);
			} else {
				$this->devicePropertiesStatesManager->setValue(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
						DevicesStates\Property::PENDING_FIELD => false,
					]),
				);
			}

			return true;
		}

		if ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
			$this->channelPropertiesStatesManager->setValue(
				$property,
				Utils\ArrayHash::from([
					DevicesStates\Property::PENDING_FIELD => $now->format(DateTimeInterface::ATOM),
				]),
			);
		} else {
			$this->devicePropertiesStatesManager->setValue(
				$property,
				Utils\ArrayHash::from([
					DevicesStates\Property::PENDING_FIELD => $now->format(DateTimeInterface::ATOM),
				]),
			);
		}

		$group = $outlet = null;
		$parameter = $property->getIdentifier();

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
			$parameter = Helpers\Transformer::devicePropertyToParameter($parameter);
		}

		if ($channel !== null) {
			if (preg_match(Sonoff\Constants::CHANNEL_GROUP, $channel->getIdentifier(), $matches) === 1) {
				if (array_key_exists('outlet', $matches)) {
					$outlet = intval($matches['outlet']);
				}

				if ($outlet !== null) {
					if ($parameter === Types\Parameter::SWITCH) {
						$group = Types\ChannelGroup::SWITCHES;
					} elseif ($parameter === Types\Parameter::STARTUP) {
						$group = Types\ChannelGroup::CONFIGURE;
					} elseif ($parameter === Types\Parameter::PULSE || $parameter === Types\Parameter::PULSE_WIDTH) {
						$group = Types\ChannelGroup::PULSES;
					}
				}
			}
		}

		try {
			if ($this->connectorHelper->getClientMode($connector)->equalsValue(Types\ClientMode::AUTO)) {
				$deferred = new Promise\Deferred();

				if ($this->deviceHelper->getIpAddress($device) !== null) {
					$client = $this->connectionManager->getLanConnection();

					$client->setDeviceState(
						$device->getIdentifier(),
						$this->deviceHelper->getIpAddress($device),
						$this->deviceHelper->getPort($device),
						$parameter,
						$expectedValue,
						$group,
						$outlet,
					)
						->then(static function () use ($deferred): void {
							$deferred->resolve(true);
						})
						->catch(
							function () use ($deferred, $connector, $device, $parameter, $expectedValue, $group, $outlet): void {
								$client = $this->connectionManager->getCloudApiConnection($connector);

								$client->setThingState(
									$device->getIdentifier(),
									$parameter,
									$expectedValue,
									$group,
									$outlet,
								)
									->then(static function () use ($deferred): void {
										$deferred->resolve(true);
									})
									->catch(static function (Throwable $ex) use ($deferred): void {
										$deferred->reject($ex);
									});
							},
						);
				} else {
					$client = $this->connectionManager->getCloudApiConnection($connector);

					$client->setThingState(
						$device->getIdentifier(),
						$parameter,
						$expectedValue,
						$group,
						$outlet,
					)
						->then(static function () use ($deferred): void {
							$deferred->resolve(true);
						})
						->catch(static function (Throwable $ex) use ($deferred): void {
							$deferred->reject($ex);
						});
				}

				$result = $deferred->promise();
			} elseif ($this->connectorHelper->getClientMode($connector)->equalsValue(Types\ClientMode::CLOUD)) {
				$client = $this->connectionManager->getCloudApiConnection($connector);

				if (!$client->isConnected()) {
					$client->connect();
				}

				$result = $client->setThingState(
					$device->getIdentifier(),
					$parameter,
					$expectedValue,
					$group,
					$outlet,
				);
			} elseif ($this->connectorHelper->getClientMode($connector)->equalsValue(Types\ClientMode::LAN)) {
				if ($this->deviceHelper->getIpAddress($device) === null) {
					throw new Exceptions\InvalidState('Device IP address is not configured');
				}

				$client = $this->connectionManager->getLanConnection();

				$result = $client->setDeviceState(
					$device->getIdentifier(),
					$this->deviceHelper->getIpAddress($device),
					$this->deviceHelper->getPort($device),
					$parameter,
					$expectedValue,
					$group,
					$outlet,
				);
			} else {
				return true;
			}
		} catch (Exceptions\InvalidState $ex) {
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $connector->getId()->toString(),
						'device' => $device->getId()->toString(),
						'state' => MetadataTypes\ConnectionState::STATE_ALERT,
					],
				),
			);

			$this->logger->error(
				'Device is not properly configured',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'write-property-state-message-consumer',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'property' => [
						'id' => $property->getId()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		} catch (Exceptions\CloudApiCall | Exceptions\LanApiCall $ex) {
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $connector->getId()->toString(),
						'device' => $device->getId()->toString(),
						'state' => MetadataTypes\ConnectionState::STATE_DISCONNECTED,
					],
				),
			);

			$extra = [];

			if ($ex instanceof Exceptions\CloudApiCall) {
				$extra = [
					'request' => [
						'method' => $ex->getRequest()?->getMethod(),
						'url' => $ex->getRequest() !== null ? strval($ex->getRequest()->getUri()) : null,
						'body' => $ex->getRequest()?->getBody()->getContents(),
					],
					'response' => [
						'body' => $ex->getResponse()?->getBody()->getContents(),
					],
				];
			}

			$this->logger->error(
				'Calling device api failed',
				array_merge(
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'write-property-state-message-consumer',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $connector->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
						],
						'property' => [
							'id' => $property->getId()->toString(),
						],
						'data' => $entity->toArray(),
					],
					$extra,
				),
			);

			return true;
		}

		$result->then(
			function () use ($property): void {
				$now = $this->dateTimeFactory->getNow();

				if ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
					$state = $this->channelPropertiesStatesManager->getValue($property);

					if ($state?->getExpectedValue() !== null) {
						$this->channelPropertiesStatesManager->setValue(
							$property,
							Utils\ArrayHash::from([
								DevicesStates\Property::PENDING_FIELD => $now->format(DateTimeInterface::ATOM),
							]),
						);
					}
				} else {
					$state = $this->devicePropertiesStatesManager->getValue($property);

					if ($state?->getExpectedValue() !== null) {
						$this->devicePropertiesStatesManager->setValue(
							$property,
							Utils\ArrayHash::from([
								DevicesStates\Property::PENDING_FIELD => $now->format(DateTimeInterface::ATOM),
							]),
						);
					}
				}
			},
			function (Throwable $ex) use ($connector, $device, $property, $entity): void {
				if ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
					$this->channelPropertiesStatesManager->setValue(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
							DevicesStates\Property::PENDING_FIELD => false,
						]),
					);
				} else {
					$this->devicePropertiesStatesManager->setValue(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
							DevicesStates\Property::PENDING_FIELD => false,
						]),
					);
				}

				$extra = [];

				if ($ex instanceof Exceptions\CloudApiCall) {
					$extra = [
						'request' => [
							'method' => $ex->getRequest()?->getMethod(),
							'url' => $ex->getRequest() !== null ? strval($ex->getRequest()->getUri()) : null,
							'body' => $ex->getRequest()?->getBody()->getContents(),
						],
						'response' => [
							'body' => $ex->getResponse()?->getBody()->getContents(),
						],
					];
				}

				if ($ex instanceof Exceptions\CloudApiCall || $ex instanceof Exceptions\LanApiCall) {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $connector->getId()->toString(),
								'identifier' => $device->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::STATE_DISCONNECTED,
							],
						),
					);

				} else {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $connector->getId()->toString(),
								'identifier' => $device->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::STATE_LOST,
							],
						),
					);
				}

				$this->logger->error(
					'Could write state to device',
					array_merge(
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
							'type' => 'write-property-state-message-consumer',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $connector->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
							'property' => [
								'id' => $property->getId()->toString(),
							],
							'data' => $entity->toArray(),
						],
						$extra,
					),
				);
			},
		);

		$this->logger->debug(
			'Consumed write sub device state message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
				'type' => 'write-property-state-message-consumer',
				'connector' => [
					'id' => $connector->getId()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'property' => [
					'id' => $property->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
