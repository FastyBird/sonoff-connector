<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           06.05.23
 */

namespace FastyBird\Connector\Sonoff\Hydrators\Devices;

use Doctrine\Common;
use Doctrine\Persistence;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Schemas;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\JsonApi\Helpers;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;
use FastyBird\Module\Devices\Models as DevicesModels;
use Fig\Http\Message\StatusCodeInterface;
use IPub\JsonAPIDocument;
use Nette\Localization;
use Ramsey\Uuid;
use function is_string;

/**
 * Sonoff device entity hydrator
 *
 * @extends DevicesHydrators\Devices\Device<Entities\Devices\Device>
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device extends DevicesHydrators\Devices\Device
{

	public function __construct(
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		Persistence\ManagerRegistry $managerRegistry,
		Localization\Translator $translator,
		Helpers\CrudReader|null $crudReader = null,
		Common\Cache\Cache|null $cache = null,
	)
	{
		parent::__construct($managerRegistry, $translator, $crudReader, $cache);
	}

	public function getEntityName(): string
	{
		return Entities\Devices\Device::class;
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApiError
	 */
	protected function hydrateConnectorRelationship(
		JsonAPIDocument\Objects\IRelationshipObject $relationship,
		JsonAPIDocument\Objects\IResourceObjectCollection|null $included,
		Entities\Devices\Device|null $entity,
	): Entities\Connectors\Connector
	{
		if (
			$relationship->getData() instanceof JsonAPIDocument\Objects\IResourceIdentifierObject
			&& is_string($relationship->getData()->getId())
			&& Uuid\Uuid::isValid($relationship->getData()->getId())
		) {
			$connector = $this->connectorsRepository->find(
				Uuid\Uuid::fromString($relationship->getData()->getId()),
				Entities\Connectors\Connector::class,
			);

			if ($connector !== null) {
				return $connector;
			}
		}

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			$this->translator->translate('//sonoff-connector.base.messages.invalidRelation.heading'),
			$this->translator->translate('//sonoff-connector.base.messages.invalidRelation.message'),
			[
				'pointer' => '/data/relationships/' . Schemas\Devices\Device::RELATIONSHIPS_CONNECTOR . '/data/id',
			],
		);
	}

}
