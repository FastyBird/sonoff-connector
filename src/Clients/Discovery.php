<?php declare(strict_types = 1);

/**
 * Discovery.php
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

use BadMethodCallException;
use Evenement;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use Nette\Utils;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use SplObjectStorage;
use stdClass;
use Throwable;
use function array_key_exists;
use function array_map;
use function assert;
use function count;
use function get_object_vars;
use function in_array;
use function intval;
use function is_array;
use function method_exists;
use function property_exists;
use function React\Async\async;
use function React\Async\await;
use function sprintf;
use const DIRECTORY_SEPARATOR;

/**
 * Devices discovery client
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Discovery implements Evenement\EventEmitterInterface
{

	use Nette\SmartObject;
	use Evenement\EventEmitterTrait;

	private const LAN_SEARCH_TIMEOUT = 60;

	private EventLoop\TimerInterface|null $handlerTimer = null;

	/** @var SplObjectStorage<Entities\Clients\DiscoveredCloudDevice, null> */
	private SplObjectStorage $discoveredDevices;

	/** @var array<string, Entities\Clients\DiscoveredLocalDevice> */
	private array $foundLocalDevices = [];

	private API\LanApi|null $lanApiConnection = null;

	private API\CloudApi|null $cloudApiConnection = null;

	public function __construct(
		private readonly Entities\SonoffConnector $connector,
		private readonly API\LanApiFactory $lanApiFactory,
		private readonly API\CloudApiFactory $cloudApiFactory,
		private readonly Helpers\Entity $entityHelper,
		private readonly Queue\Queue $queue,
		private readonly Sonoff\Logger $logger,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
		$this->discoveredDevices = new SplObjectStorage();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Throwable
	 */
	public function discover(): void
	{
		$this->discoveredDevices = new SplObjectStorage();

		$this->discoverCloudDevices()
			->then(async(function (): void {
				$mode = $this->connector->getClientMode();

				if (
					$mode->equalsValue(Types\ClientMode::LAN)
					|| $mode->equalsValue(Types\ClientMode::AUTO)
				) {
					await($this->discoverLanDevices());
				}

				$this->handleFoundDevices();

				$this->discoveredDevices->rewind();

				$devices = [];

				foreach ($this->discoveredDevices as $device) {
					$devices[] = $device;
				}

				$this->emit('finished', [$devices]);
			}))
			->catch(function (): void {
				$this->emit('failed');
			});
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function disconnect(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);
			$this->handlerTimer = null;
		}

		$this->getCloudApiConnection()->disconnect();
		$this->getLanConnection()->disconnect();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function discoverCloudDevices(): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->logger->debug(
			'Starting cloud devices discovery',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
				'type' => 'discovery-client',
			],
		);

		$apiClient = $this->getCloudApiConnection();

		try {
			$apiClient->connect();

		} catch (Exceptions\CloudApiCall $ex) {
			$this->logger->error(
				'Log into eWelink account failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'discovery-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			return Promise\reject($ex);
		}

		$apiClient->getFamily()
			->then(function (Entities\API\Cloud\Family $family) use ($deferred, $apiClient): void {
				$apiClient->getFamilyThings($family->getFamilyId())
					->then(function (Entities\API\Cloud\Things $things) use ($deferred): void {
						$this->handleFoundCloudDevices($things);

						$deferred->resolve(true);
					})
					->catch(function (Throwable $ex) use ($deferred): void {
						$this->logger->error(
							'Loading devices from cloud failed',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
								'type' => 'discovery-client',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
							],
						);

						$deferred->reject($ex);
					});
			})
			->catch(function (Throwable $ex) use ($deferred): void {
				$this->logger->error(
					'Loading homes from cloud failed',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'discovery-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
					],
				);

				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws BadMethodCallException
	 * @throws Exceptions\InvalidState
	 * @throws RuntimeException
	 */
	private function discoverLanDevices(): Promise\PromiseInterface
	{
		$this->logger->debug(
			'Starting lan devices discovery',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
				'type' => 'discovery-client',
			],
		);

		$deferred = new Promise\Deferred();

		$apiClient = $this->getLanConnection();

		$apiClient->on(
			'message',
			function (Entities\API\Lan\DeviceEvent $message): void {
				$this->foundLocalDevices[$message->getId()] = $this->entityHelper->create(
					Entities\Clients\DiscoveredLocalDevice::class,
					[
						'ipAddress' => $message->getIpAddress(),
						'domain' => $message->getDomain(),
						'port' => $message->getPort(),
					],
				);
			},
		);

		// Searching timeout
		$this->handlerTimer = $this->eventLoop->addTimer(
			self::LAN_SEARCH_TIMEOUT,
			async(static function () use ($deferred, $apiClient): void {
				$apiClient->disconnect();

				$deferred->resolve(true);
			}),
		);

		$apiClient->connect();

		return $deferred->promise();
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	private function handleFoundCloudDevices(Entities\API\Cloud\Things $things): void
	{
		foreach ($things->getDevices() as $device) {
			try {
				$mappingConfiguration = Utils\Json::decode($this->getUiidMapping($device->getExtra()->getUiid()));
				assert($mappingConfiguration instanceof stdClass);
			} catch (Exceptions\InvalidState $ex) {
				$this->logger->error(
					'Params mapping for device UIID could not be loaded',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'discovery-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'device' => [
							'id' => $device->getDeviceId(),
							'uiid' => $device->getExtra()->getUiid(),
						],
					],
				);

				continue;
			} catch (Utils\JsonException $ex) {
				$this->logger->error(
					'Device params mapping could not be prepared for mapping',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'discovery-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'device' => [
							'id' => $device->getDeviceId(),
							'uiid' => $device->getExtra()->getUiid(),
							'state' => $device->getState()?->toArray(),
						],
					],
				);

				continue;
			}

			$parameters = [];

			foreach (get_object_vars($mappingConfiguration) as $identifier => $configuration) {
				assert($configuration instanceof stdClass);

				if ($identifier === Types\ChannelGroup::RF_LIST) {
					// TODO: Not supported now
				} else {
					if (property_exists($configuration, 'properties') && property_exists($configuration, 'length')) {
						if (in_array($identifier, $device->getDenyFeatures(), true)) {
							continue;
						}

						$count = intval($configuration->length);

						if ($identifier === Types\ChannelGroup::SWITCHES) {
							$count = intval($configuration->length);

							if ($device->getState() !== null) {
								if (
									method_exists($device->getState(), 'getSwitches')
									&& is_array($device->getState()->getSwitches())
								) {
									$count = count($device->getState()->getSwitches());
								} elseif (
									method_exists($device->getState(), 'getConfiguration')
									&& is_array($device->getState()->getConfiguration())
								) {
									$count = count($device->getState()->getConfiguration());
								} elseif (
									method_exists($device->getState(), 'getPulses')
									&& is_array($device->getState()->getPulses())
								) {
									$count = count($device->getState()->getPulses());
								}
							}
						}

						for ($i = 0; $i < $count; $i++) {
							foreach (get_object_vars(
								$configuration->properties,
							) as $subIdentifier => $subConfiguration) {
								assert($subConfiguration instanceof stdClass);

								$parameters[] = [
									'group' => (
										property_exists($subConfiguration, 'group')
											? $subConfiguration->group
											: $subIdentifier
										) . '_' . $i,
									'identifier' => $subIdentifier,
									'name' => $subConfiguration->name,
									'type' => $subConfiguration->type,
									'dataType' => $subConfiguration->data_type,
									'format' => property_exists($subConfiguration, 'format')
										? $subConfiguration->format
										: null,
									'settable' => $subConfiguration->settable,
									'queryable' => $subConfiguration->queryable,
									'scale' => property_exists($subConfiguration, 'scale')
										? $subConfiguration->scale
										: null,
								];
							}
						}
					} else {
						if (
							in_array($identifier, $device->getDenyFeatures(), true)
							|| (
								$identifier === Types\Parameter::STATUS_LED
								&& in_array('sled', $device->getDenyFeatures(), true)
							)
						) {
							continue;
						}

						$parameters[] = [
							'group' => property_exists($configuration, 'group') ? $configuration->group : $identifier,
							'identifier' => $identifier,
							'name' => $configuration->name,
							'type' => $configuration->type,
							'dataType' => $configuration->data_type,
							'format' => property_exists($configuration, 'format') ? $configuration->format : null,
							'settable' => $configuration->settable,
							'queryable' => $configuration->queryable,
							'scale' => property_exists($configuration, 'scale')
								? $configuration->scale
								: null,
						];
					}
				}
			}

			$this->discoveredDevices->attach(
				$this->entityHelper->create(
					Entities\Clients\DiscoveredCloudDevice::class,
					[
						'id' => $device->getDeviceId(),
						'apiKey' => $device->getApiKey(),
						'deviceKey' => $device->getDeviceKey(),
						'uiid' => $device->getExtra()->getUiid(),
						'name' => $device->getName(),
						'description' => $device->getExtra()->getDescription(),
						'brandName' => $device->getBrandName(),
						'brandLogo' => $device->getBrandLogo(),
						'productModel' => $device->getProductModel(),
						'model' => $device->getExtra()->getModel(),
						'mac' => $device->getExtra()->getMac(),
						'parameters' => $parameters,
					],
				),
			);
		}
	}

	private function handleFoundDevices(): void
	{
		$this->discoveredDevices->rewind();

		foreach ($this->discoveredDevices as $device) {
			$localConfiguration = null;

			if (array_key_exists($device->getId(), $this->foundLocalDevices)) {
				$localConfiguration = $this->foundLocalDevices[$device->getId()];
			}

			try {
				$this->queue->append(
					$this->entityHelper->create(
						Entities\Messages\StoreDevice::class,
						[
							'connector' => $this->connector->getId(),
							'id' => $device->getId(),
							'apiKey' => $device->getApiKey(),
							'deviceKey' => $device->getDeviceKey(),
							'uiid' => $device->getUiid(),
							'name' => $device->getName(),
							'description' => $device->getDescription(),
							'brandName' => $device->getBrandName(),
							'brandLogo' => $device->getBrandLogo(),
							'productModel' => $device->getProductModel(),
							'model' => $device->getModel(),
							'mac' => $device->getMac(),
							'ipAddress' => $localConfiguration?->getIpAddress(),
							'domain' => $localConfiguration?->getDomain(),
							'port' => $localConfiguration?->getPort(),
							'parameters' => array_map(
							// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
								static fn (Entities\Clients\DiscoveredDeviceParameter $parameter): array => [
									'group' => $parameter->getGroup(),
									'identifier' => $parameter->getIdentifier(),
									'name' => $parameter->getName(),
									'type' => $parameter->getType(),
									'dataType' => $parameter->getDataType(),
									'format' => $parameter->getFormat(),
									'settable' => $parameter->isSettable(),
									'queryable' => $parameter->isQueryable(),
									'scale' => $parameter->getScale(),
								],
								$device->getParameters(),
							),
						],
					),
				);
			} catch (Exceptions\Runtime $ex) {
				$this->logger->error(
					'Found device could not be attached to processing queue',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'discovery-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'device' => $device->toArray(),
					],
				);
			}
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getUiidMapping(int $uiid): string
	{
		try {
			$mapping = Utils\FileSystem::read(
				Sonoff\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'uiid' . DIRECTORY_SEPARATOR . sprintf(
					'uiid%d_mapping.json',
					$uiid,
				),
			);

		} catch (Nette\IOException) {
			throw new Exceptions\InvalidState('Validation schema for response could not be loaded');
		}

		return $mapping;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function getCloudApiConnection(): API\CloudApi
	{
		if ($this->cloudApiConnection === null) {
			$this->cloudApiConnection = $this->cloudApiFactory->create(
				$this->connector->getUsername(),
				$this->connector->getPassword(),
				$this->connector->getAppId(),
				$this->connector->getAppSecret(),
				$this->connector->getRegion(),
			);
		}

		return $this->cloudApiConnection;
	}

	private function getLanConnection(): API\LanApi
	{
		if ($this->lanApiConnection === null) {
			$this->lanApiConnection = $this->lanApiFactory->create();
		}

		return $this->lanApiConnection;
	}

}
