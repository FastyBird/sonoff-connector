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
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queries;
use FastyBird\Connector\Sonoff\Queue;
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

	public function __construct(
		Entities\SonoffConnector $connector,
		DevicesModels\Devices\DevicesRepository $devicesRepository,
		DevicesUtilities\DeviceConnection $deviceConnectionManager,
		DevicesUtilities\DevicePropertiesStates $devicePropertiesStates,
		DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		DateTimeFactory\Factory $dateTimeFactory,
		EventLoop\LoopInterface $eventLoop,
		private readonly bool $autoMode,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\Entity $entityHelper,
		private readonly Queue\Queue $queue,
		private readonly Sonoff\Logger $logger,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
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

		$cloudApi = $this->connectionManager->getCloudApiConnection($this->connector);
		$cloudApi->connect();

		if (
			$cloudApi->isConnected()
			&& $cloudApi->getAccessToken() !== null
			&& $cloudApi->getUser() !== null
		) {
			$cloudWs = $this->connectionManager->getCloudWsConnection($this->connector);

			$cloudWs->on(
				'message',
				function (Entities\API\Sockets\DeviceConnectionStateEvent|Entities\API\Sockets\DeviceStateEvent $message): void {
					if ($message instanceof Entities\API\Sockets\DeviceConnectionStateEvent) {
						$this->queue->append(
							$this->entityHelper->create(
								Entities\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $this->connector->getId(),
									'identifier' => $message->getDeviceId(),
									'state' => $message->isOnline()
										? MetadataTypes\ConnectionState::get(
											MetadataTypes\ConnectionState::STATE_CONNECTED,
										)
										: MetadataTypes\ConnectionState::get(
											MetadataTypes\ConnectionState::STATE_DISCONNECTED,
										),
								],
							),
						);
					} else {
						$this->handleDeviceState($message);
					}
				},
			);

			$cloudWs->on('error', function (Throwable $ex): void {
				$this->logger->error(
					'An error occurred in eWelink cloud websockets client',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'cloud-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF),
						'An error occurred in eWelink cloud websockets client',
						$ex,
					),
				);
			});

			$cloudWs->connect()
				->then(function (): void {
					$this->logger->debug(
						'Created eWelink cloud websockets client',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
							'type' => 'cloud-client',
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
						],
					);
				})
				->otherwise(function (Throwable $ex): void {
					$this->logger->error(
						'eWelink cloud websockets client could not be created',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
							'type' => 'cloud-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
						],
					);

					$this->dispatcher?->dispatch(
						new DevicesEvents\TerminateConnector(
							MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF),
							'eWelink cloud websockets client could not be created',
							$ex,
						),
					);
				});
		} else {
			$this->logger->error(
				'Could not create connection to sockets server',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'cloud-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
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
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function disconnect(): void
	{
		$this->connectionManager->getCloudWsConnection($this->connector)->disconnect();

		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}

		$this->connectionManager->getCloudApiConnection($this->connector)->disconnect();
	}

	/**
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function readInformation(Entities\SonoffDevice $device): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->connectionManager
			->getCloudApiConnection($this->connector)
			->getThing($device->getIdentifier())
			->then(function (Entities\API\Cloud\Device $result) use ($deferred, $device): void {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $this->connector->getId(),
								'identifier' => $device->getIdentifier(),
								'state' => $result->isOnline()
									? MetadataTypes\ConnectionState::get(
										MetadataTypes\ConnectionState::STATE_CONNECTED,
									)
									: MetadataTypes\ConnectionState::get(
										MetadataTypes\ConnectionState::STATE_DISCONNECTED,
									),
							],
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
								'id' => $this->connector->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
						],
					);

					if (!$ex instanceof Exceptions\CloudApiCall) {
						$this->dispatcher?->dispatch(
							new DevicesEvents\TerminateConnector(
								MetadataTypes\ConnectorSource::get(
									MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
								),
								'Could not call eWelink api',
								$ex,
							),
						);
					}

					$deferred->reject($ex);
				});

		return $deferred->promise();
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\CloudApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function readState(Entities\SonoffDevice $device): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if (
			$this->connectionManager->getCloudWsConnection($this->connector)->isConnected()
			&& $device->getApiKey() !== null
		) {
			$this->connectionManager
				->getCloudWsConnection($this->connector)
				->readStates(
					$device->getIdentifier(),
					$device->getApiKey(),
				)
				->then(function (Entities\API\Sockets\DeviceStateEvent $result) use ($deferred): void {
						$this->handleDeviceState($result);

						$deferred->resolve(true);
				})
					->otherwise(function () use ($deferred, $device): void {
						$this->connectionManager
							->getCloudApiConnection($this->connector)
							->getThingState(
								$device->getIdentifier(),
							)
							->then(function (Entities\API\Cloud\DeviceState $result) use ($deferred): void {
									$this->handleDeviceState($result);

									$deferred->resolve(true);
							})
								->otherwise(function (Throwable $ex) use ($deferred, $device): void {
									$this->logger->error(
										'Calling eWelink cloud failed',
										[
											'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
											'type' => 'cloud-client',
											'exception' => BootstrapHelpers\Logger::buildException($ex),
											'connector' => [
												'id' => $this->connector->getId()->toString(),
											],
											'device' => [
												'id' => $device->getId()->toString(),
											],
										],
									);

									if (!$ex instanceof Exceptions\CloudApiCall) {
										$this->dispatcher?->dispatch(
											new DevicesEvents\TerminateConnector(
												MetadataTypes\ConnectorSource::get(
													MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
												),
												'Could not call eWelink api',
												$ex,
											),
										);
									}

									$deferred->reject($ex);
								});
					});
		} else {
			$this->connectionManager
				->getCloudApiConnection($this->connector)
				->getThingState($device->getIdentifier())
				->then(function (Entities\API\Cloud\DeviceState $result) use ($deferred): void {
						$this->handleDeviceState($result);

						$deferred->resolve(true);
				})
					->otherwise(function (Throwable $ex) use ($deferred, $device): void {
						$this->logger->error(
							'Calling eWelink cloud failed',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
								'type' => 'cloud-client',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
								'device' => [
									'id' => $device->getId()->toString(),
								],
							],
						);

						if (!$ex instanceof Exceptions\CloudApiCall) {
							$this->dispatcher?->dispatch(
								new DevicesEvents\TerminateConnector(
									MetadataTypes\ConnectorSource::get(
										MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
									),
									'Could not call eWelink api',
									$ex,
								),
							);
						}

						$deferred->reject($ex);
					});
		}

		return $deferred->promise();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function handleDeviceState(
		Entities\API\Cloud\DeviceState|Entities\API\Sockets\DeviceStateEvent $message,
	): void
	{
		if ($message->getState() === null) {
			return;
		}

		$findDeviceQuery = new Queries\FindDevices();
		$findDeviceQuery->byIdentifier($message->getDeviceId());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\SonoffDevice::class);

		if ($device === null) {
			return;
		}

		$this->queue->append(
			$this->entityHelper->create(
				Entities\Messages\StoreParametersStates::class,
				[
					'connector' => $this->connector->getId(),
					'identifier' => $device->getIdentifier(),
					'parameters' => $message->getState()->toStates(),
				],
			),
		);
	}

}
