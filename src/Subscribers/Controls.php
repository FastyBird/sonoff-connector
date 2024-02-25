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
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Types as DevicesTypes;
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
		private readonly DevicesModels\Entities\Connectors\Controls\ControlsRepository $connectorsControlsRepository,
		private readonly DevicesModels\Entities\Connectors\Controls\ControlsManager $connectorsControlsManager,
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
	 * @throws ApplicationExceptions\InvalidState
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if ($entity instanceof Entities\Connectors\Connector) {
			$findConnectorControlQuery = new DevicesQueries\Entities\FindConnectorControls();
			$findConnectorControlQuery->forConnector($entity);
			$findConnectorControlQuery->byName(DevicesTypes\ControlName::DISCOVER->value);

			$discoveryControl = $this->connectorsControlsRepository->findOneBy($findConnectorControlQuery);

			if ($discoveryControl === null) {
				$this->connectorsControlsManager->create(Utils\ArrayHash::from([
					'name' => DevicesTypes\ControlName::DISCOVER->value,
					'connector' => $entity,
				]));
			}

			$findConnectorControlQuery = new DevicesQueries\Entities\FindConnectorControls();
			$findConnectorControlQuery->forConnector($entity);
			$findConnectorControlQuery->byName(DevicesTypes\ControlName::REBOOT->value);

			$rebootControl = $this->connectorsControlsRepository->findOneBy($findConnectorControlQuery);

			if ($rebootControl === null) {
				$this->connectorsControlsManager->create(Utils\ArrayHash::from([
					'name' => DevicesTypes\ControlName::REBOOT->value,
					'connector' => $entity,
				]));
			}
		}
	}

}
