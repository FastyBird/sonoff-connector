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

use Evenement;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Consumers;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use SplObjectStorage;
use stdClass;
use Throwable;
use function array_key_exists;
use function array_map;
use function assert;
use function count;
use function get_object_vars;
use function intval;
use function property_exists;
use function React\Async\async;
use function React\Async\await;
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

	private API\CloudApi|null $cloudApi = null;

	private API\LanApi|null $lanApi = null;

	private EventLoop\TimerInterface|null $handlerTimer = null;

	/** @var SplObjectStorage<Entities\Clients\DiscoveredDevice, null> */
	private SplObjectStorage $discoveredDevices;

	/** @var array<string, Entities\Clients\DiscoveredDeviceLocal> */
	private array $foundLocalDevices = [];

	public function __construct(
		private readonly Entities\SonoffConnector $connector,
		private readonly API\LanApiFactory $lanApiFactory,
		private readonly API\CloudApiFactory $cloudApiFactory,
		private readonly Consumers\Messages $consumer,
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
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

		$mode = $this->connector->getClientMode();

		$result = $this->discoverCloudDevices();

		if ($result === false) {
			$this->emit('failed');

			return;
		}

		if (
			$mode->equalsValue(Types\ClientMode::MODE_LAN)
			|| $mode->equalsValue(Types\ClientMode::MODE_AUTO)
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
	}

	public function disconnect(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);
			$this->handlerTimer = null;
		}

		$this->cloudApi?->disconnect();
		$this->lanApi?->disconnect();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function discoverCloudDevices(): bool
	{
		$this->cloudApi = $this->cloudApiFactory->create(
			$this->connector->getIdentifier(),
			$this->connector->getUsername(),
			$this->connector->getPassword(),
			$this->connector->getAppId(),
			$this->connector->getAppSecret(),
			$this->connector->getRegion(),
		);

		$this->logger->debug(
			'Starting cloud devices discovery',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
				'type' => 'discovery-client',
			],
		);

		try {
			$this->cloudApi->connect();

		} catch (Exceptions\CloudApiCall $ex) {
			$this->logger->error(
				'Log into eWelink account failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'discovery-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			return false;
		}

		try {
			$homes = $this->cloudApi->getHomes(false);

		} catch (Exceptions\CloudApiCall $ex) {
			$this->logger->error(
				'Loading homes from cloud failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'discovery-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			return false;
		}

		try {
			$devices = $this->cloudApi->getHomeThings($homes->getCurrentFamilyId(), false);

		} catch (Exceptions\CloudApiCall $ex) {
			$this->logger->error(
				'Loading devices from cloud failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'discovery-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			return false;
		}

		$this->handleFoundCloudDevices($devices);

		return true;
	}

	private function discoverLanDevices(): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->lanApi = $this->lanApiFactory->create(
			$this->connector->getIdentifier(),
		);

		$this->lanApi->on(
			'message',
			function (Entities\API\LanMessage $message): void {
				$this->foundLocalDevices[$message->getId()] = new Entities\Clients\DiscoveredDeviceLocal(
					$message->getIpAddress(),
					$message->getDomain(),
					$message->getPort(),
				);
			},
		);

		// Searching timeout
		$this->handlerTimer = $this->eventLoop->addTimer(
			self::LAN_SEARCH_TIMEOUT,
			async(function () use ($deferred): void {
				$this->lanApi?->disconnect();
				$this->lanApi = null;

				$deferred->resolve();
			}),
		);

		$this->logger->debug(
			'Starting lan devices discovery',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
				'type' => 'discovery-client',
			],
		);

		$this->lanApi->connect();

		return $deferred->promise();
	}

	private function handleFoundCloudDevices(Entities\API\ThingList $devices): void
	{
		foreach ($devices->getDevices() as $device) {
			try {
				$parameters = $this->schemaValidator->validate(
					Utils\Json::encode($device->getParams()),
					$this->getUiidSchema($device->getExtra()->getUiid()),
				);
			} catch (Exceptions\InvalidState $ex) {
				$this->logger->error(
					'Validation schema for device UIID could not be loaded',
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
				try {
					$this->logger->error(
						'Device params could not be prepared for validation',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
							'type' => 'discovery-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'device' => [
								'id' => $device->getDeviceId(),
								'uiid' => $device->getExtra()->getUiid(),
								'params' => Utils\Json::encode($device->getParams()),
							],
						],
					);
				} catch (Utils\JsonException) {
					// Just ignore this exception
				}

				continue;
			} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
				try {
					$this->logger->error(
						'Device params could not be validated against schema',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
							'type' => 'discovery-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'device' => [
								'id' => $device->getDeviceId(),
								'uiid' => $device->getExtra()->getUiid(),
								'params' => Utils\Json::encode($device->getParams()),
							],
						],
					);
				} catch (Utils\JsonException) {
					// Just ignore this exception
				}

				continue;
			}

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
							'params' => $device->getParams() !== null ? (array) $device->getParams() : null,
						],
					],
				);

				continue;
			}

			$properties = [];

			foreach (get_object_vars($mappingConfiguration) as $identifier => $configuration) {
				assert($configuration instanceof stdClass);

				if ($identifier === Types\ChannelGroup::GROUP_OUTLET) {
					$count = intval($configuration->length);

					if ($parameters->offsetExists(Types\ParameterGroup::GROUP_SWITCHES)) {
						$count = count((array) $parameters->offsetGet(Types\ParameterGroup::GROUP_SWITCHES));
					} elseif ($parameters->offsetExists(Types\ParameterGroup::GROUP_CONFIGURE)) {
						$count = count((array) $parameters->offsetGet(Types\ParameterGroup::GROUP_CONFIGURE));
					} elseif ($parameters->offsetExists(Types\ParameterGroup::GROUP_PULSES)) {
						$count = count((array) $parameters->offsetGet(Types\ParameterGroup::GROUP_PULSES));
					}

					for ($i = 0; $i < $count; $i++) {
						foreach (get_object_vars($configuration->properties) as $subIdentifier => $subConfiguration) {
							assert($subConfiguration instanceof stdClass);

							$properties[] = new Entities\Clients\DiscoveredDeviceProperty(
								$identifier . '_' . $i,
								$subIdentifier,
								$subConfiguration->name,
								$subConfiguration->type,
								MetadataTypes\DataType::get($subConfiguration->data_type),
								property_exists($subConfiguration, 'format') ? $subConfiguration->format : null,
								$subConfiguration->settable,
								$subConfiguration->queryable,
							);
						}
					}
				} elseif ($identifier === Types\ChannelGroup::GROUP_RF_LIST) {
					// TODO: Not supported now
				} else {
					$properties[] = new Entities\Clients\DiscoveredDeviceProperty(
						$configuration->type,
						$identifier,
						$configuration->name,
						$configuration->type,
						MetadataTypes\DataType::get($configuration->data_type),
						property_exists($configuration, 'format') ? $configuration->format : null,
						$configuration->settable,
						$configuration->queryable,
					);
				}
			}

			$this->discoveredDevices->attach(
				new Entities\Clients\DiscoveredDevice(
					$device->getDeviceId(),
					$device->getApiKey(),
					$device->getDeviceKey(),
					$device->getExtra()->getUiid(),
					$device->getName(),
					$device->getExtra()->getDescription(),
					$device->getBrandName(),
					$device->getBrandLogo(),
					$device->getProductModel(),
					$device->getExtra()->getModel(),
					$device->getExtra()->getMac(),
					$properties,
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

			$this->consumer->append(new Entities\Messages\DiscoveredDevice(
				$this->connector->getId(),
				$device->getId(),
				$device->getApiKey(),
				$device->getDeviceKey(),
				$device->getUiid(),
				$device->getName(),
				$device->getDescription(),
				$device->getBrandName(),
				$device->getBrandLogo(),
				$device->getProductModel(),
				$device->getModel(),
				$device->getMac(),
				$localConfiguration?->getIpAddress(),
				$localConfiguration?->getDomain(),
				$localConfiguration?->getPort(),
				array_map(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
					static fn (Entities\Clients\DiscoveredDeviceProperty $property): Entities\Messages\DiscoveredDeviceProperty => new Entities\Messages\DiscoveredDeviceProperty(
						$property->getGroup(),
						API\Transformer::deviceParameterNameToProperty($property->getIdentifier()),
						$property->getName(),
						Types\DeviceParameterType::get($property->getType()),
						$property->getDataType(),
						$property->getFormat(),
						$property->isSettable(),
						$property->isQueryable(),
					),
					$device->getProperties(),
				),
			));
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getUiidSchema(int $uiid): string
	{
		try {
			$schema = Utils\FileSystem::read(
				Sonoff\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'uiid' . DIRECTORY_SEPARATOR . 'uiid' . $uiid . '.json',
			);

		} catch (Nette\IOException) {
			throw new Exceptions\InvalidState('Validation schema for response could not be loaded');
		}

		return $schema;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getUiidMapping(int $uiid): string
	{
		try {
			$mapping = Utils\FileSystem::read(
				Sonoff\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'uiid' . DIRECTORY_SEPARATOR . 'uiid' . $uiid . '_mapping.json',
			);

		} catch (Nette\IOException) {
			throw new Exceptions\InvalidState('Validation schema for response could not be loaded');
		}

		return $mapping;
	}

}
