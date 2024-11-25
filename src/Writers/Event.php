<?php declare(strict_types = 1);

/**
 * Event.php
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

use FastyBird\Connector\Sonoff\Documents;
use FastyBird\Connector\Sonoff\Queries;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Core\Tools\Helpers as ToolsHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Events as DevicesEvents;
use Symfony\Component\EventDispatcher;
use Throwable;

/**
 * Event based properties writer
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Event extends Periodic implements Writer, EventDispatcher\EventSubscriberInterface
{

	public const NAME = 'event';

	public static function getSubscribedEvents(): array
	{
		return [
			DevicesEvents\DevicePropertyStateEntityCreated::class => 'stateChanged',
			DevicesEvents\DevicePropertyStateEntityUpdated::class => 'stateChanged',
			DevicesEvents\ChannelPropertyStateEntityCreated::class => 'stateChanged',
			DevicesEvents\ChannelPropertyStateEntityUpdated::class => 'stateChanged',
		];
	}

	public function stateChanged(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		DevicesEvents\DevicePropertyStateEntityCreated|DevicesEvents\DevicePropertyStateEntityUpdated|DevicesEvents\ChannelPropertyStateEntityCreated|DevicesEvents\ChannelPropertyStateEntityUpdated $event,
	): void
	{
		try {
			if (
				$event->getGet()->getExpectedValue() === null
				|| $event->getGet()->getPending() !== true
			) {
				return;
			}

			if (
				$event instanceof DevicesEvents\DevicePropertyStateEntityCreated
				|| $event instanceof DevicesEvents\DevicePropertyStateEntityUpdated
			) {
				$findDeviceQuery = new Queries\Configuration\FindDevices();
				$findDeviceQuery->forConnector($this->connector);
				$findDeviceQuery->byId($event->getProperty()->getDevice());

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
							'property' => $event->getProperty()->getId(),
							'state' => $event->getGet()->toArray(),
						],
					),
				);
			} else {
				$findChannelQuery = new Queries\Configuration\FindChannels();
				$findChannelQuery->byId($event->getProperty()->getChannel());

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
							'property' => $event->getProperty()->getId(),
							'state' => $event->getGet()->toArray(),
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
					'type' => 'event-writer',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);
		}
	}

}
