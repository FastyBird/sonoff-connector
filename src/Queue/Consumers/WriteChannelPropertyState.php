<?php declare(strict_types = 1);

/**
 * WriteChannelPropertyState.php
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
use FastyBird\Connector\Sonoff\Documents;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queries;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette;
use React\Promise;
use RuntimeException;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function array_merge;
use function intval;
use function preg_match;
use function React\Async\async;
use function React\Async\await;
use function strval;

/**
 * Write state to device message consumer
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WriteChannelPropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Queue\Queue $queue,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\MessageBuilder $entityHelper,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Helpers\Device $deviceHelper,
		private readonly Sonoff\Logger $logger,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly DateTimeFactory\Clock $clock,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws Throwable
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\WriteChannelPropertyState) {
			return false;
		}

		$findConnectorQuery = new Queries\Configuration\FindConnectors();
		$findConnectorQuery->byId($message->getConnector());

		$connector = $this->connectorsConfigurationRepository->findOneBy(
			$findConnectorQuery,
			Documents\Connectors\Connector::class,
		);

		if ($connector === null) {
			$this->logger->error(
				'Connector could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::SONOFF->value,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$findDeviceQuery = new Queries\Configuration\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byId($message->getDevice());

		$device = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\Device::class,
		);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::SONOFF->value,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$findChannelQuery = new Queries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byId($message->getChannel());

		$channel = $this->channelsConfigurationRepository->findOneBy(
			$findChannelQuery,
			Documents\Channels\Channel::class,
		);

		if ($channel === null) {
			$this->logger->error(
				'Channel could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::SONOFF->value,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byId($message->getProperty());

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findChannelPropertyQuery,
			DevicesDocuments\Channels\Properties\Dynamic::class,
		);

		if ($property === null) {
			$this->logger->error(
				'Channel property could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::SONOFF->value,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $channel->getId()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		if (!$property->isSettable()) {
			$this->logger->warning(
				'Property is not writable',
				[
					'source' => MetadataTypes\Sources\Connector::SONOFF->value,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $channel->getId()->toString(),
					],
					'property' => [
						'id' => $property->getId()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$state = $message->getState();

		if ($state === null) {
			return true;
		}

		$expectedValue = MetadataUtilities\Value::flattenValue($state->getExpectedValue());

		if ($expectedValue === null) {
			await($this->channelPropertiesStatesManager->setPendingState(
				$property,
				false,
				MetadataTypes\Sources\Connector::SONOFF,
			));

			return true;
		}

		$now = $this->clock->getNow();
		$pending = $state->getPending();

		if (
			$pending === false
			|| (
				$pending instanceof DateTimeInterface
				&& (float) $now->format('Uv') - (float) $pending->format('Uv') <= Sonoff\Constants::WRITE_DEBOUNCE_DELAY
			)
		) {
			return true;
		}

		await($this->channelPropertiesStatesManager->setPendingState(
			$property,
			true,
			MetadataTypes\Sources\Connector::SONOFF,
		));

		$group = $outlet = null;
		$parameter = $property->getIdentifier();

		if (preg_match(Sonoff\Constants::CHANNEL_GROUP, $channel->getIdentifier(), $matches) === 1) {
			if (array_key_exists('outlet', $matches)) {
				$outlet = intval($matches['outlet']);
			}

			if ($outlet !== null) {
				if ($parameter === Types\Parameter::SWITCH->value) {
					$group = Types\ChannelGroup::SWITCHES;
				} elseif ($parameter === Types\Parameter::STARTUP->value) {
					$group = Types\ChannelGroup::CONFIGURE;
				} elseif (
					$parameter === Types\Parameter::PULSE->value
					|| $parameter === Types\Parameter::PULSE_WIDTH->value
				) {
					$group = Types\ChannelGroup::PULSES;
				}
			}
		}

		try {
			if ($this->connectorHelper->getClientMode($connector) === Types\ClientMode::AUTO) {
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
			} elseif ($this->connectorHelper->getClientMode($connector) === Types\ClientMode::CLOUD) {
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
			} elseif ($this->connectorHelper->getClientMode($connector) === Types\ClientMode::LAN) {
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
				await($this->channelPropertiesStatesManager->setPendingState(
					$property,
					false,
					MetadataTypes\Sources\Connector::SONOFF,
				));

				return true;
			}
		} catch (Exceptions\InvalidState $ex) {
			$this->queue->append(
				$this->entityHelper->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $connector->getId()->toString(),
						'device' => $device->getId()->toString(),
						'state' => DevicesTypes\ConnectionState::ALERT,
					],
				),
			);

			await($this->channelPropertiesStatesManager->setPendingState(
				$property,
				false,
				MetadataTypes\Sources\Connector::SONOFF,
			));

			$this->logger->error(
				'Device is not properly configured',
				[
					'source' => MetadataTypes\Sources\Connector::SONOFF->value,
					'type' => 'write-channel-property-state-message-consumer',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $channel->getId()->toString(),
					],
					'property' => [
						'id' => $property->getId()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		} catch (Exceptions\CloudApiCall | Exceptions\LanApiCall $ex) {
			$this->queue->append(
				$this->entityHelper->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $connector->getId()->toString(),
						'device' => $device->getId()->toString(),
						'state' => DevicesTypes\ConnectionState::DISCONNECTED,
					],
				),
			);

			await($this->channelPropertiesStatesManager->setPendingState(
				$property,
				false,
				MetadataTypes\Sources\Connector::SONOFF,
			));

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
						'source' => MetadataTypes\Sources\Connector::SONOFF->value,
						'type' => 'write-channel-property-state-message-consumer',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $message->getConnector()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
						],
						'channel' => [
							'id' => $channel->getId()->toString(),
						],
						'property' => [
							'id' => $property->getId()->toString(),
						],
						'data' => $message->toArray(),
					],
					$extra,
				),
			);

			return true;
		}

		$result->then(
			function () use ($device, $channel, $property, $message): void {
				$this->logger->debug(
					'Channel state was successfully sent to device',
					[
						'source' => MetadataTypes\Sources\Connector::SONOFF->value,
						'type' => 'write-channel-property-state-message-consumer',
						'connector' => [
							'id' => $message->getConnector()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
						],
						'channel' => [
							'id' => $channel->getId()->toString(),
						],
						'property' => [
							'id' => $property->getId()->toString(),
						],
						'data' => $message->toArray(),
					],
				);
			},
			async(function (Throwable $ex) use ($connector, $device, $channel, $property, $message): void {
				await($this->channelPropertiesStatesManager->setPendingState(
					$property,
					false,
					MetadataTypes\Sources\Connector::SONOFF,
				));

				$extra = [];

				if ($ex instanceof Exceptions\CloudApiCall || $ex instanceof Exceptions\LanApiCall) {
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

					$this->queue->append(
						$this->entityHelper->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $connector->getId()->toString(),
								'identifier' => $device->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::DISCONNECTED,
							],
						),
					);

				} elseif ($ex instanceof Exceptions\CloudApiError || $ex instanceof Exceptions\LanApiError) {
					$this->queue->append(
						$this->entityHelper->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $connector->getId()->toString(),
								'identifier' => $device->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::ALERT,
							],
						),
					);
				}

				$this->logger->error(
					'Could write state to device',
					array_merge(
						[
							'source' => MetadataTypes\Sources\Connector::SONOFF->value,
							'type' => 'write-channel-property-state-message-consumer',
							'exception' => ApplicationHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $message->getConnector()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
							'channel' => [
								'id' => $channel->getId()->toString(),
							],
							'property' => [
								'id' => $property->getId()->toString(),
							],
							'data' => $message->toArray(),
						],
						$extra,
					),
				);
			}),
		);

		$this->logger->debug(
			'Consumed write device state message',
			[
				'source' => MetadataTypes\Sources\Connector::SONOFF->value,
				'type' => 'write-channel-property-state-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'channel' => [
					'id' => $channel->getId()->toString(),
				],
				'property' => [
					'id' => $property->getId()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

}
