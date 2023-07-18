<?php declare(strict_types = 1);

/**
 * Lan.php
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
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette\Utils;
use Psr\EventDispatcher;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use Throwable;
use function assert;
use function in_array;

/**
 * Lan client
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Lan extends ClientProcess implements Client
{

	use TDeviceStatus;

	private API\LanApi $lanApiApi;

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
		API\LanApiFactory $lanApiApiFactory,
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

		$this->lanApiApi = $lanApiApiFactory->create(
			$this->connector->getIdentifier(),
		);
	}

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

		$this->lanApiApi->connect();

		$this->lanApiApi->on('message', function (Entities\API\LanMessage $message): void {
			$findDeviceQuery = new DevicesQueries\FindDevices();
			$findDeviceQuery->byIdentifier($message->getId());

			$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\SonoffDevice::class);

			if ($device !== null) {
				assert($device instanceof Entities\SonoffDevice);

				foreach ($message->getData() as $data) {
					if ($message->isEncrypted()) {
						if ($device->getDeviceKey() === null) {
							continue;
						}

						$data = API\Transformer::decryptMessage(
							$data,
							$device->getDeviceKey(),
							$message->getIv() ?? '',
						);

						if ($data === false) {
							$this->logger->warning(
								'Received device info message could not be decoded',
								[
									'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
									'type' => 'lan-client',
									'connector' => [
										'id' => $this->connector->getPlainId(),
									],
									'device' => [
										'id' => $device->getPlainId(),
									],
								],
							);

							continue;
						}
					}

					try {
						$params = Utils\ArrayHash::from((array) Utils\Json::decode($data, Utils\Json::FORCE_ARRAY));

						$this->handleDeviceStatus(new Entities\API\DeviceStatus(null, $message->getId(), $params));
					} catch (Utils\JsonException) {
						continue;
					}
				}
			}
		});

		if (!$this->autoMode) {
			$this->writer->connect($this->connector, $this);
		}
	}

	public function disconnect(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}

		if (!$this->autoMode) {
			$this->writer->disconnect($this->connector, $this);
		}

		$this->lanApiApi->disconnect();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\LanApiCall
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
		if ($device->getIpAddress() === null) {
			return Promise\resolve(true);
		}

		$deferred = new Promise\Deferred();

		$this->lanApiApi->setDeviceStatus(
			$device->getIdentifier(),
			$device->getDeviceKey(),
			$device->getIpAddress(),
			$device->getPort(),
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
			->otherwise(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\LanApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function readInformation(Entities\SonoffDevice $device): Promise\PromiseInterface
	{
		if ($device->getIpAddress() === null) {
			return Promise\reject(new Exceptions\InvalidState('Device ip address is not configured'));
		}

		$deferred = new Promise\Deferred();

		$this->lanApiApi->getDeviceInfo(
			$device->getIdentifier(),
			$device->getDeviceKey(),
			$device->getIpAddress(),
			$device->getPort(),
		)
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
				if ($ex instanceof Exceptions\LanApiCall) {
					if (
							in_array(
								$ex->getCode(),
								[
									API\LanApi::ERROR_INVALID_JSON,
									API\LanApi::ERROR_UNAUTHORIZED,
									API\LanApi::ERROR_DEVICE_ID_INVALID,
									API\LanApi::ERROR_INVALID_PARAMETER,
								],
								true,
							)
						&& !$this->autoMode
					) {
						$this->ignoredDevices[] = $device->getPlainId();
					}

					$this->logger->warning(
						'Calling device api for reading status failed',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
							'type' => 'lan-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
							'device' => [
								'id' => $device->getPlainId(),
							],
						],
					);

					$deferred->reject($ex);

				} else {
					$this->logger->error(
						'Could not call device lan api',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
							'type' => 'lan-client',
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
							'Could not call device local api',
							$ex,
						),
					);

					$deferred->reject($ex);
				}
			});

		return $deferred->promise();
	}

	protected function readStatus(Entities\SonoffDevice $device): Promise\PromiseInterface
	{
		// Not supported by lan device
		return Promise\reject(new Exceptions\NotSupported('Reading device status is not supported for lan devices'));
	}

}
