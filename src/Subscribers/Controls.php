<?php declare(strict_types = 1);

/**
 * Controls.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           14.05.23
 */

namespace FastyBird\Connector\Sonoff\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use Nette\Utils;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Controls implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Connectors\Controls\ControlsRepository $controlsRepository,
		private readonly DevicesModels\Connectors\Controls\ControlsManager $controlsManager,
	)
	{
	}

	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::postPersist,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if ($entity instanceof Entities\SonoffConnector) {
			$findConnectorControlQuery = new DevicesQueries\FindConnectorControls();
			$findConnectorControlQuery->forConnector($entity);
			$findConnectorControlQuery->byName(Types\ConnectorControlName::NAME_REBOOT);

			$rebootControl = $this->controlsRepository->findOneBy($findConnectorControlQuery);

			if ($rebootControl === null) {
				$this->controlsManager->create(Utils\ArrayHash::from([
					'name' => Types\ConnectorControlName::NAME_REBOOT,
					'connector' => $entity,
				]));
			}

			$findConnectorControlQuery = new DevicesQueries\FindConnectorControls();
			$findConnectorControlQuery->forConnector($entity);
			$findConnectorControlQuery->byName(Types\ConnectorControlName::NAME_DISCOVER);

			$discoverControl = $this->controlsRepository->findOneBy($findConnectorControlQuery);

			if ($discoverControl === null) {
				$this->controlsManager->create(Utils\ArrayHash::from([
					'name' => Types\ConnectorControlName::NAME_DISCOVER,
					'connector' => $entity,
				]));
			}
		}
	}

}
