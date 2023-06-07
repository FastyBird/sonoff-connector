<?php declare(strict_types = 1);

/**
 * SonoffConnector.php
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

namespace FastyBird\Connector\Sonoff\Hydrators;

use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Sonoff connector entity hydrator
 *
 * @extends DevicesHydrators\Connectors\Connector<Entities\SonoffConnector>
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SonoffConnector extends DevicesHydrators\Connectors\Connector
{

	public function getEntityName(): string
	{
		return Entities\SonoffConnector::class;
	}

}
