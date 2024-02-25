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
use FastyBird\Connector\Sonoff\Documents;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queries;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use InvalidArgumentException;
use Psr\EventDispatcher;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use Throwable;
use TypeError;
use ValueError;
use function React\Async\async;

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
		Helpers\Device $deviceHelper,
		DevicesUtilities\DeviceConnection $deviceConnectionManager,
		DateTimeFactory\Factory $dateTimeFactory,
		EventLoop\LoopInterface $eventLoop,
		private readonly Documents\Connectors\Connector $connector,
		private readonly bool $autoMode,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\MessageBuilder $entityHelper,
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
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function connect(): void
	{
		if (!$this->autoMode) {
			$this->processedDevices = [];
			$this->processedDevicesCommands = [];

			$this->handlerTimer = null;

			$this->eventLoop->addTimer(
				self::HANDLER_START_DELAY,
				async(function (): void {
					$this->registerLoopHandler();
				}),
			);

			$findDevicesQuery = new Queries\Configuration\FindDevices();
			$findDevicesQuery->forConnector($this->connector);

			$devices = $this->devicesConfigurationRepository->findAllBy(
				$findDevicesQuery,
				Documents\Devices\Device::class,
			);

			foreach ($devices as $device) {
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

			$cloudWs->onMessage[] = function (API\Messages\Message $message): void {
				if ($message instanceof API\Messages\Response\Sockets\DeviceConnectionStateEvent) {
					$this->queue->append(
						$this->entityHelper->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $this->connector->getId(),
								'identifier' => $message->getDeviceId(),
								'state' => $message->isOnline()
									? DevicesTypes\ConnectionState::CONNECTED
									: DevicesTypes\ConnectionState::DISCONNECTED,
							],
						),
					);
				} elseif ($message instanceof API\Messages\Response\Sockets\DeviceStateEvent) {
					$this->handleDeviceState($message);
				}
			};

			$cloudWs->onError[] = function (Throwable $ex): void {
				$this->logger->error(
					'An error occurred in eWelink cloud websockets client',
					[
						'source' => MetadataTypes\Sources\Connector::SONOFF->value,
						'type' => 'cloud-client',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						MetadataTypes\Sources\Connector::SONOFF,
						'An error occurred in eWelink cloud websockets client',
						$ex,
					),
				);
			};

			$cloudWs->connect()
				->then(function (): void {
					$this->logger->debug(
						'Created eWelink cloud websockets client',
						[
							'source' => MetadataTypes\Sources\Connector::SONOFF->value,
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
							'source' => MetadataTypes\Sources\Connector::SONOFF->value,
							'type' => 'cloud-client',
							'exception' => ApplicationHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
						],
					);

					$this->dispatcher?->dispatch(
						new DevicesEvents\TerminateConnector(
							MetadataTypes\Sources\Connector::SONOFF,
							'eWelink cloud websockets client could not be created',
							$ex,
						),
					);
				});
		} else {
			$this->logger->error(
				'Could not create connection to sockets server',
				[
					'source' => MetadataTypes\Sources\Connector::SONOFF->value,
					'type' => 'cloud-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			$this->dispatcher?->dispatch(
				new DevicesEvents\TerminateConnector(
					MetadataTypes\Sources\Connector::SONOFF,
					'Could not create connection to sockets server',
				),
			);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function readInformation(
		Documents\Devices\Device $device,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->connectionManager
			->getCloudApiConnection($this->connector)
			->getThing($device->getIdentifier())
			->then(function (API\Messages\Response\Cloud\Device $result) use ($deferred, $device): void {
					$this->queue->append(
						$this->entityHelper->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $this->connector->getId(),
								'identifier' => $device->getIdentifier(),
								'state' => $result->isOnline()
									? DevicesTypes\ConnectionState::CONNECTED
									: DevicesTypes\ConnectionState::DISCONNECTED,
							],
						),
					);

					$deferred->resolve(true);
			})
				->catch(function (Throwable $ex) use ($deferred, $device): void {
					$this->logger->error(
						'Could not call cloud openapi',
						[
							'source' => MetadataTypes\Sources\Connector::SONOFF->value,
							'type' => 'cloud-client',
							'exception' => ApplicationHelpers\Logger::buildException($ex),
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
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $device->getConnector(),
									'identifier' => $device->getIdentifier(),
									'state' => DevicesTypes\ConnectionState::ALERT,
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
								MetadataTypes\Sources\Connector::SONOFF,
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function readState(Documents\Devices\Device $device): Promise\PromiseInterface
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
				->then(function (API\Messages\Response\Sockets\DeviceStateEvent $result) use ($deferred): void {
						$this->handleDeviceState($result);

						$deferred->resolve(true);
				})
					->catch(function () use ($deferred, $device): void {
						$this->connectionManager
							->getCloudApiConnection($this->connector)
							->getThingState(
								$device->getIdentifier(),
							)
							->then(function (API\Messages\Response\Cloud\DeviceState $result) use ($deferred): void {
									$this->handleDeviceState($result);

									$deferred->resolve(true);
							})
								->catch(function (Throwable $ex) use ($deferred, $device): void {
									$this->logger->error(
										'Calling eWelink cloud failed',
										[
											'source' => MetadataTypes\Sources\Connector::SONOFF->value,
											'type' => 'cloud-client',
											'exception' => ApplicationHelpers\Logger::buildException($ex),
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
												Queue\Messages\StoreDeviceConnectionState::class,
												[
													'connector' => $device->getConnector(),
													'identifier' => $device->getIdentifier(),
													'state' => DevicesTypes\ConnectionState::ALERT,
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
												MetadataTypes\Sources\Connector::SONOFF,
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
				->then(function (API\Messages\Response\Cloud\DeviceState $result) use ($deferred): void {
						$this->handleDeviceState($result);

						$deferred->resolve(true);
				})
					->catch(function (Throwable $ex) use ($deferred, $device): void {
						$this->logger->error(
							'Calling eWelink cloud failed',
							[
								'source' => MetadataTypes\Sources\Connector::SONOFF->value,
								'type' => 'cloud-client',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
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
									MetadataTypes\Sources\Connector::SONOFF,
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
		API\Messages\Response\Cloud\DeviceState|API\Messages\Response\Sockets\DeviceStateEvent $message,
	): void
	{
		if ($message->getState() === null) {
			return;
		}

		$findDeviceQuery = new Queries\Configuration\FindDevices();
		$findDeviceQuery->byIdentifier($message->getDeviceId());

		$device = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\Device::class,
		);

		if ($device === null) {
			return;
		}

		$this->queue->append(
			$this->entityHelper->create(
				Queue\Messages\StoreParametersStates::class,
				[
					'connector' => $this->connector->getId(),
					'identifier' => $device->getIdentifier(),
					'parameters' => $message->getState()->toStates(),
				],
			),
		);
	}

}
