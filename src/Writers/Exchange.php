<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           14.05.23
 */

namespace FastyBird\Connector\Sonoff\Writers;

use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\Documents;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queries;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Core\Application\Documents as ApplicationDocuments;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Core\Tools\Helpers as ToolsHelpers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Constants as DevicesConstants;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use React\EventLoop;
use Throwable;
use function array_merge;
use function str_starts_with;

/**
 * Exchange based properties writer
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Exchange extends Periodic implements Writer, ExchangeConsumers\Consumer
{

	public const NAME = 'exchange';

	public function __construct(
		Documents\Connectors\Connector $connector,
		Helpers\MessageBuilder $entityHelper,
		Queue\Queue $queue,
		Sonoff\Logger $logger,
		DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
		DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		DateTimeFactory\Clock $clock,
		EventLoop\LoopInterface $eventLoop,
		private readonly ExchangeConsumers\Container $consumer,
	)
	{
		parent::__construct(
			$connector,
			$entityHelper,
			$queue,
			$logger,
			$devicesConfigurationRepository,
			$channelsConfigurationRepository,
			$devicesPropertiesConfigurationRepository,
			$channelsPropertiesConfigurationRepository,
			$devicePropertiesStatesManager,
			$channelPropertiesStatesManager,
			$clock,
			$eventLoop,
		);

		$this->consumer->register($this, null, false);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function connect(): void
	{
		parent::connect();

		$this->consumer->enable(self::class);
	}

	/**
	 * @throws ExchangeExceptions\InvalidArgument
	 */
	public function disconnect(): void
	{
		parent::disconnect();

		$this->consumer->disable(self::class);
	}

	public function consume(
		MetadataTypes\Sources\Source $source,
		string $routingKey,
		ApplicationDocuments\Document|null $document,
	): void
	{
		try {
			if ($document instanceof DevicesDocuments\States\Devices\Properties\Property) {
				if (str_starts_with($routingKey, DevicesConstants::MESSAGE_BUS_DELETED_ROUTING_KEY)) {
					return;
				}

				if (
					$document->getGet()->getExpectedValue() === null
					|| $document->getPending() !== true
				) {
					return;
				}

				$findDeviceQuery = new Queries\Configuration\FindDevices();
				$findDeviceQuery->forConnector($this->connector);
				$findDeviceQuery->byId($document->getDevice());

				$device = $this->devicesConfigurationRepository->findOneBy(
					$findDeviceQuery,
					Documents\Devices\Device::class,
				);

				if ($device === null) {
					return;
				}

				$this->queue->append(
					$this->entityHelper->create(
						Queue\Messages\WriteDevicePropertyState::class,
						[
							'connector' => $this->connector->getId(),
							'device' => $device->getId(),
							'property' => $document->getId(),
							'state' => array_merge(
								$document->getGet()->toArray(),
								[
									'id' => $document->getId(),
									'valid' => $document->isValid(),
									'pending' => $document->getPending(),
								],
							),
						],
					),
				);

			} elseif ($document instanceof DevicesDocuments\States\Channels\Properties\Property) {
				if (str_starts_with($routingKey, DevicesConstants::MESSAGE_BUS_DELETED_ROUTING_KEY)) {
					return;
				}

				if (
					$document->getGet()->getExpectedValue() === null
					|| $document->getPending() !== true
				) {
					return;
				}

				$findChannelQuery = new Queries\Configuration\FindChannels();
				$findChannelQuery->byId($document->getChannel());

				$channel = $this->channelsConfigurationRepository->findOneBy(
					$findChannelQuery,
					Documents\Channels\Channel::class,
				);

				if ($channel === null) {
					return;
				}

				$findDeviceQuery = new Queries\Configuration\FindDevices();
				$findDeviceQuery->forConnector($this->connector);
				$findDeviceQuery->byId($channel->getDevice());

				$device = $this->devicesConfigurationRepository->findOneBy(
					$findDeviceQuery,
					Documents\Devices\Device::class,
				);

				if ($device === null) {
					return;
				}

				$this->queue->append(
					$this->entityHelper->create(
						Queue\Messages\WriteChannelPropertyState::class,
						[
							'connector' => $this->connector->getId(),
							'device' => $device->getId(),
							'channel' => $channel->getId(),
							'property' => $document->getId(),
							'state' => array_merge(
								$document->getGet()->toArray(),
								[
									'id' => $document->getId(),
									'valid' => $document->isValid(),
									'pending' => $document->getPending(),
								],
							),
						],
					),
				);
			}
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'Characteristic value could not be prepared for writing',
				[
					'source' => MetadataTypes\Sources\Connector::SONOFF->value,
					'type' => 'exchange-writer',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);
		}
	}

}
