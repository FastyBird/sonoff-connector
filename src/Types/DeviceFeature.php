<?php declare(strict_types = 1);

/**
 * DeviceFeature.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           21.05.23
 */

namespace FastyBird\Connector\Sonoff\Types;

use Consistence;
use function strval;

/**
 * Known device features
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DeviceFeature extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const FEATURE_LAN_CONTROL = 'localCtl';

	public const FEATURE_SWITCH_PULSE = 'pulse';

	public const FEATURE_STATUS_LED = 'sled';

	public const FEATURE_MUTEX_LOCK = 'mutexLock';

	public const FEATURE_ALL_ON_OFF = 'allOnOff';

	public const FEATURE_SWITCH_WIFI_USER_SECRET = 'switchWifi-userSecret';

	public const FEATURE_EXT_SWITCH_MODE = 'extSwitchMode';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
