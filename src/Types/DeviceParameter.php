<?php declare(strict_types = 1);

/**
 * DeviceParameter.php
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
 * Known device parameters
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DeviceParameter extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const PARAMETER_STATUS_LED = 'sledOnline';

	public const PARAMETER_FIRMWARE_VERSION = 'fwVersion';

	public const PARAMETER_RSSI = 'rssi';

	public const PARAMETER_SSID = 'ssid';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
