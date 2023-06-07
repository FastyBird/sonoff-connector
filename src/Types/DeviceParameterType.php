<?php declare(strict_types = 1);

/**
 * DeviceParameterType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           14.05.23
 */

namespace FastyBird\Connector\Sonoff\Types;

use Consistence;
use function strval;

/**
 * Device parameters types
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DeviceParameterType extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const TYPE_DEVICE = 'device';

	public const TYPE_CHANNEL = 'channel';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
