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

/**
 * Device parameters groups
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ParameterGroup: string
{

	case DEVICE = 'device';

	case LIGHT = 'light';

	case SWITCH = 'switch';

	case THERMOSTAT = 'thermostat';

	case FAN = 'fan';

	case SENSOR = 'sensor';

	case HUMIDIFIER = 'humidifier';

	case OTHER = 'other';

	case BRIDGE = 'bridge';

	case SCENE_WHITE = 'white';

	case SCENE_BRIGHT = 'bright';

	case SCENE_READ = 'read';

	case SCENE_COMPUTER = 'computer';

	case SCENE_NIGHT_LIGHT = 'nightLight';

	case SCENE_COLOR = 'color';

	case SCENE_GOOD_NIGHT = 'goodNight';

	case SCENE_PARTY = 'party';

	case SCENE_LEISURE = 'leisure';

	case SCENE_SOFT = 'soft';

	case SCENE_COLORFUL = 'colorful';

}
