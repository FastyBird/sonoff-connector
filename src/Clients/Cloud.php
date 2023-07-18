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

use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Consumers;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Writers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use InvalidArgumentException;
use Psr\EventDispatcher;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use Throwable;

/**
 * Cloud client
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Cloud extends ClientProcess implements Client
{

	use TDeviceStatus;

	private API\CloudApi $cloudApiApi;

	private API\CloudWs $cloudWs;

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function __construct(
		Entities\SonoffConnector $connector,
		DevicesModels\Devices\DevicesRepository $devicesRepository,
		DevicesUtilities\DeviceConnection $deviceConnectionManager,
		DevicesUtilities\DevicePropertiesStates $devicePropertiesStates,
		DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		DateTimeFactory\Factory $dateTimeFactory,
		EventLoop\LoopInterface $eventLoop,
		private readonly bool $autoMode,
		private readonly Consumers\Messages $consumer,
		API\CloudApiFactory $cloudApiApiFactory,
		private readonly API\CloudWsFactory $cloudWsApiFactory,
		private readonly Writers\Writer $writer,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
		parent::__construct(
			$connector,
			$devicesRepository,
			$deviceConnectionManager,
			$devicePropertiesStates,
			$channelPropertiesStates,
			$dateTimeFactory,
			$eventLoop,
		);

		$this->cloudApiApi = $cloudApiApiFactory->create(
			$this->connector->getIdentifier(),
			$this->connector->getUsername(),
			$this->connector->getPassword(),
			$this->connector->getAppId(),
			$this->connector->getAppSecret(),
			$this->connector->getRegion(),
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\CloudApiCall
	 * @throws InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function connect(): void
	{
		if (!$this->autoMode) {
			$this->processedDevices = [];
			$this->processedDevicesCommands = [];

			$this->handlerTimer = null;

			$this->eventLoop->addTimer(
				self::HANDLER_START_DELAY,
				function (): void {
					$this->registerLoopHandler();
				},
			);
		}

		$this->cloudApiApi->connect();

		if (
			$this->cloudApiApi->isConnected()
			&& $this->cloudApiApi->getAccessToken() !== null
			&& $this->cloudApiApi->getUser() !== null
		) {
			$this->cloudWs = $this->cloudWsApiFactory->create(
				$this->connector->getIdentifier(),
				$this->cloudApiApi->getAccessToken(),
				$this->connector->getAppId(),
				$this->cloudApiApi->getUser()->getApiKey(),
				$this->cloudApiApi->getRegion(),
			);

			$this->cloudWs->on(
				'message',
				function (Entities\API\DeviceState|Entities\API\DeviceStatus|Entities\API\DeviceUpdated $message): void {
					if ($message instanceof Entities\API\DeviceState) {
						$this->consumer->append(
							new Entities\Messages\DeviceState(
								$this->connector->getId(),
								$message->getDeviceId(),
								$message->isOnline() ? MetadataTypes\ConnectionState::get(
									MetadataTypes\ConnectionState::STATE_CONNECTED,
								) : MetadataTypes\ConnectionState::get(
									MetadataTypes\ConnectionState::STATE_DISCONNECTED,
								),
							),
						);
					} else {
						$this->handleDeviceStatus($message);
					}
				},
			);

			$this->cloudWs->on('error', function (Throwable $ex): void {
				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF),
						'eWelink cloud websockets client could not be created',
						$ex,
					),
				);
			});

			$this->cloudWs->connect();
		} else {
			$this->logger->error(
				'Could not create connection to sockets server',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-client',
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
				],
			);

			$this->dispatcher?->dispatch(
				new DevicesEvents\TerminateConnector(
					MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF),
					'Could not create connection to sockets server',
				),
			);
		}

		if (!$this->autoMode) {
			$this->writer->connect($this->connector, $this);
		}
	}

	public function disconnect(): void
	{
		$this->cloudWs->disconnect();

		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}

		if (!$this->autoMode) {
			$this->writer->disconnect($this->connector, $this);
		}

		$this->cloudApiApi->disconnect();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\CloudApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function writeState(
		Entities\SonoffDevice $device,
		string $parameter,
		string|int|float|bool $value,
		string|null $group = null,
		int|null $index = null,
	): Promise\PromiseInterface
	{
		if ($this->cloudWs->isConnected() && $device->getApiKey() !== null) {
			$deferred = new Promise\Deferred();

			$this->cloudWs->writeState(
				$device->getIdentifier(),
				$device->getApiKey(),
				$parameter,
				$value,
				$group,
				$index,
			)
				->then(function (Entities\API\Entity $result) use ($deferred): void {
					if ($result instanceof Entities\API\DeviceUpdated) {
						$this->handleDeviceStatus($result);
					}

					$deferred->resolve(true);
				})
				->otherwise(function () use ($deferred, $device, $parameter, $value, $group, $index): void {
					$this->cloudApiApi->setThingStatus(
						$device->getIdentifier(),
						$parameter,
						$value,
						$group,
						$index,
					)
						->then(static function () use ($deferred): void {
							$deferred->resolve(true);
						})
						->otherwise(static function (Throwable $ex) use ($deferred): void {
							$deferred->reject($ex);
						});
				});

			return $deferred->promise();
		} else {
			return $this->cloudApiApi->setThingStatus(
				$device->getIdentifier(),
				$parameter,
				$value,
				$group,
				$index,
			);
		}
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 */
	protected function readInformation(Entities\SonoffDevice $device): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->cloudApiApi->getSpecifiedThings($device->getIdentifier())
			->then(function (Entities\API\Device $deviceInformation) use ($deferred, $device): void {
				$this->consumer->append(
					new Entities\Messages\DeviceState(
						$device->getConnector()->getId(),
						$device->getIdentifier(),
						$deviceInformation->isOnline() ? MetadataTypes\ConnectionState::get(
							MetadataTypes\ConnectionState::STATE_CONNECTED,
						) : MetadataTypes\ConnectionState::get(
							MetadataTypes\ConnectionState::STATE_DISCONNECTED,
						),
					),
				);

				$deferred->resolve(true);
			})
			->otherwise(function (Throwable $ex) use ($deferred, $device): void {
				$this->logger->error(
					'Could not call cloud openapi',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'cloud-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
						'device' => [
							'id' => $device->getPlainId(),
						],
					],
				);

				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF),
						'Could not call cloud openapi',
						$ex,
					),
				);

				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 */
	protected function readStatus(Entities\SonoffDevice $device): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->cloudApiApi->getThingStatus($device->getIdentifier())
			->then(function (Entities\API\DeviceStatus $deviceStatus) use ($deferred): void {
				$this->handleDeviceStatus($deviceStatus);

				$deferred->resolve(true);
			})
			->otherwise(function (Throwable $ex) use ($deferred, $device): void {
				if (!$ex instanceof Exceptions\CloudApiCall) {
					$this->logger->error(
						'Calling eWelink cloud failed',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
							'type' => 'cloud-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
							'device' => [
								'id' => $device->getPlainId(),
							],
						],
					);

					$this->dispatcher?->dispatch(
						new DevicesEvents\TerminateConnector(
							MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF),
							'Could not call eWelink api',
							$ex,
						),
					);

					$deferred->reject($ex);
				}
			});

		return $deferred->promise();
	}

}
