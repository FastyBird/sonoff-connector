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

use Exception;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use function assert;

/**
 * Exchange based properties writer
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Exchange implements Writer, ExchangeConsumers\Consumer
{

	use Nette\SmartObject;

	public const NAME = 'exchange';

	public function __construct(
		private readonly Entities\SonoffConnector $connector,
		private readonly Helpers\Entity $entityHelper,
		private readonly Queue\Queue $queue,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly ExchangeConsumers\Container $consumer,
	)
	{
	}

	/**
	 * @throws ExchangeExceptions\InvalidArgument
	 */
	public function connect(): void
	{
		$this->consumer->enable(self::class);
	}

	/**
	 * @throws ExchangeExceptions\InvalidArgument
	 */
	public function disconnect(): void
	{
		$this->consumer->disable(self::class);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 */
	public function consume(
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\AutomatorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataDocuments\Document|null $entity,
	): void
	{
		if ($entity instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
			$findChannelQuery = new DevicesQueries\Entities\FindChannels();
			$findChannelQuery->byId($entity->getChannel());

			$channel = $this->channelsRepository->findOneBy($findChannelQuery);

			if ($channel === null) {
				return;
			}

			$device = $channel->getDevice();
			assert($device instanceof Entities\SonoffDevice);

			if (!$device->getConnector()->getId()->equals($this->connector->getId())) {
				return;
			}

			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\WriteChannelPropertyState::class,
					[
						'connector' => $this->connector->getId()->toString(),
						'device' => $device->getId()->toString(),
						'channel' => $channel->getId()->toString(),
						'property' => $entity->getId()->toString(),
					],
				),
			);
		}
	}

}
