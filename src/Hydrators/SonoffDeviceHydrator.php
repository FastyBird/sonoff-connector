<?php declare(strict_types = 1);

/**
 * SonoffDeviceHydrator.php
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
 * Sonoff device entity hydrator
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Hydrators
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-extends DevicesModuleHydrators\Devices\DeviceHydrator<Entities\ISonoffDevice>
 */
final class SonoffDeviceHydrator extends DevicesModuleHydrators\Devices\DeviceHydrator
{

	/**
	 * {@inheritDoc}
	 */
	public function getEntityName(): string
	{
		return Entities\SonoffDevice::class;
	}

}
