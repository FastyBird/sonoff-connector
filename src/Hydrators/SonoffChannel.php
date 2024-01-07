<?php declare(strict_types = 1);

/**
 * SonoffChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           07.01.24
 */

namespace FastyBird\Connector\Sonoff\Hydrators;

use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Sonoff channel entity hydrator
 *
 * @extends DevicesHydrators\Channels\Channel<Entities\SonoffChannel>
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SonoffChannel extends DevicesHydrators\Channels\Channel
{

	public function getEntityName(): string
	{
		return Entities\SonoffChannel::class;
	}

}
