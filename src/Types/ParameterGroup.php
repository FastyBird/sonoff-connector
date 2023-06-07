<?php declare(strict_types = 1);

/**
 * ParameterGroup.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           28.05.23
 */

namespace FastyBird\Connector\Sonoff\Types;

use Consistence;
use function strval;

/**
 * Device parameters groups
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ParameterGroup extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const GROUP_SWITCHES = 'switches';

	public const GROUP_CONFIGURE = 'configure';

	public const GROUP_PULSES = 'pulses';

	public const GROUP_RF_LIST = 'rfList';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
