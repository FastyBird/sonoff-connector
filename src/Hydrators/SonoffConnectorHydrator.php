<?php declare(strict_types = 1);

/**
 * SonoffConnectorHydrator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Hydrators
 * @since          0.1.0
 *
 * @date           25.04.22
 */

namespace FastyBird\SonoffConnector\Hydrators;

use FastyBird\DevicesModule\Hydrators as DevicesModuleHydrators;
use FastyBird\SonoffConnector\Entities;

/**
 * Sonoff connector entity hydrator
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Hydrators
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-extends DevicesModuleHydrators\Connectors\ConnectorHydrator<Entities\ISonoffConnector>
 */
final class SonoffConnectorHydrator extends DevicesModuleHydrators\Connectors\ConnectorHydrator
{

	/**
	 * {@inheritDoc}
	 */
	public function getEntityName(): string
	{
		return Entities\SonoffConnector::class;
	}

}
