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

	public const DEVICE = 'device';

	public const LIGHT = 'light';

	public const SWITCH = 'switch';

	public const THERMOSTAT = 'thermostat';

	public const FAN = 'fan';

	public const SENSOR = 'sensor';

	public const HUMIDIFIER = 'humidifier';

	public const OTHER = 'other';

	public const BRIDGE = 'bridge';

	public const SCENE_WHITE = 'white';

	public const SCENE_BRIGHT = 'bright';

	public const SCENE_READ = 'read';

	public const SCENE_COMPUTER = 'computer';

	public const SCENE_NIGHT_LIGHT = 'nightLight';

	public const SCENE_COLOR = 'color';

	public const SCENE_GOOD_NIGHT = 'goodNight';

	public const SCENE_PARTY = 'party';

	public const SCENE_LEISURE = 'leisure';

	public const SCENE_SOFT = 'soft';

	public const SCENE_COLORFUL = 'colorful';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
