<?php declare(strict_types = 1);

/**
 * Parameter.php
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
 * Device parameters
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Parameter extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const PARAMETER_SWITCH = 'switch';

	public const PARAMETER_STARTUP = 'startup';

	public const PARAMETER_PULSE = 'pulse';

	public const PARAMETER_PULSE_WIDTH = 'pulseWidth';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
