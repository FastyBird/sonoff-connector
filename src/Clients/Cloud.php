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
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
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

	/**
	 * @param DevicesModels\Configuration\Devices\Repository<MetadataDocuments\DevicesModule\Device> $devicesConfigurationRepository
	 */
	public function __construct(
		Helpers\Device $deviceHelper,
		DevicesUtilities\DeviceConnection $deviceConnectionManager,
		DateTimeFactory\Factory $dateTimeFactory,
		EventLoop\LoopInterface $eventLoop,
		private readonly MetadataDocuments\DevicesModule\Connector $connector,
		private readonly bool $autoMode,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\Entity $entityHelper,
		private readonly Queue\Queue $queue,
		private readonly Sonoff\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		parent::__construct(
			$deviceHelper,
			$deviceConnectionManager,
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

			$findDevicesQuery = new DevicesQueries\Configuration\FindDevices();
			$findDevicesQuery->forConnector($this->connector);

			foreach ($this->devicesConfigurationRepository->findAllBy($findDevicesQuery) as $device) {
				$this->devices[$device->getId()->toString()] = $device;
			}
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
				->catch(function (Throwable $ex): void {
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
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
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
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	protected function readInformation(MetadataDocuments\DevicesModule\Device $device): Promise\PromiseInterface
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
				->catch(function (Throwable $ex) use ($deferred, $device): void {
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

					if ($ex instanceof Exceptions\CloudApiError) {
						$this->queue->append(
							$this->entityHelper->create(
								Entities\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $device->getConnector(),
									'identifier' => $device->getIdentifier(),
									'state' => MetadataTypes\ConnectionState::get(
										MetadataTypes\ConnectionState::STATE_ALERT,
									),
								],
							),
						);
					}

					if (
						!$ex instanceof Exceptions\CloudApiCall
						&& !$ex instanceof Exceptions\CloudApiError
					) {
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
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	protected function readState(MetadataDocuments\DevicesModule\Device $device): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if (
			$this->connectionManager->getCloudWsConnection($this->connector)->isConnected()
			&& $this->deviceHelper->getApiKey($device) !== null
		) {
			$this->connectionManager
				->getCloudWsConnection($this->connector)
				->readStates(
					$device->getIdentifier(),
					$this->deviceHelper->getApiKey($device),
				)
				->then(function (Entities\API\Sockets\DeviceStateEvent $result) use ($deferred): void {
						$this->handleDeviceState($result);

						$deferred->resolve(true);
				})
					->catch(function () use ($deferred, $device): void {
						$this->connectionManager
							->getCloudApiConnection($this->connector)
							->getThingState(
								$device->getIdentifier(),
							)
							->then(function (Entities\API\Cloud\DeviceState $result) use ($deferred): void {
									$this->handleDeviceState($result);

									$deferred->resolve(true);
							})
								->catch(function (Throwable $ex) use ($deferred, $device): void {
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

									if ($ex instanceof Exceptions\CloudApiError) {
										$this->queue->append(
											$this->entityHelper->create(
												Entities\Messages\StoreDeviceConnectionState::class,
												[
													'connector' => $device->getConnector(),
													'identifier' => $device->getIdentifier(),
													'state' => MetadataTypes\ConnectionState::get(
														MetadataTypes\ConnectionState::STATE_ALERT,
													),
												],
											),
										);
									}

									if (
										!$ex instanceof Exceptions\CloudApiCall
										&& !$ex instanceof Exceptions\CloudApiError
									) {
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
					->catch(function (Throwable $ex) use ($deferred, $device): void {
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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function handleDeviceState(
		Entities\API\Cloud\DeviceState|Entities\API\Sockets\DeviceStateEvent $message,
	): void
	{
		if ($message->getState() === null) {
			return;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byIdentifier($message->getDeviceId());

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

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
