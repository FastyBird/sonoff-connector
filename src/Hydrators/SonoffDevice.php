<?php declare(strict_types = 1);

/**
 * SonoffDevice.php
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
 * Sonoff device entity hydrator
 *
 * @extends DevicesHydrators\Devices\Device<Entities\SonoffDevice>
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SonoffDevice extends DevicesHydrators\Devices\Device
{

	public function getEntityName(): string
	{
		return Entities\SonoffDevice::class;
	}

}
