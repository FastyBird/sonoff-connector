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

/**
 * Device parameters
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Parameter: string
{

	case STATUS_LED = 'sledOnline';

	case FIRMWARE_VERSION = 'fwVersion';

	case RSSI = 'rssi';

	case SSID = 'ssid';

	case BSSID = 'bssid';

	case SWITCH = 'switch';

	case STARTUP = 'startup';

	case PULSE = 'pulse';

	case PULSE_WIDTH = 'pulseWidth';

	case MINIMUM_BRIGHTNESS = 'brightMin';

	case MAXIMUM_BRIGHTNESS = 'brightMax';

	case MODE = 'mode';

	case POWER = 'power';

	case VOLTAGE = 'voltage';

	case CURRENT = 'current';

	case CONSUMPTION = 'oneKwh';

	case CONSUMPTION_DATA = 'oneKwhData';

	case START_TIME = 'startTime';

	case END_TIME = 'endTime';

	case MAIN_SWITCH = 'mainSwitch';

	case DEVICE_TYPE = 'deviceType';

	case SENSOR_TYPE = 'sensorType';

	case CURRENT_TEMPERATURE = 'currentTemperature';

	case CURRENT_HUMIDITY = 'currentHumidity';

	case CHANNEL_0 = 'channel0';

	case CHANNEL_1 = 'channel1';

	case CHANNEL_2 = 'channel2';

	case CHANNEL_3 = 'channel3';

	case CHANNEL_4 = 'channel4';

	case TYPE = 'type';

	case FAN = 'fan';

	case SPEED = 'speed';

	case SHAKE = 'shake';

	case DUSTY = 'dusty';

	case NOISE = 'noise';

	case LIGHT = 'light';

	case TEMPERATURE = 'temperature';

	case HUMIDITY = 'humidity';

	case STATE = 'state';

	case WATER = 'water';

	case LIGHT_TYPE = 'light_type';

	case LIGHT_SWITCH = 'lightswitch';

	case LIGHT_MODE = 'lightmode';

	case LIGHT_RED_COLOR = 'lightRcolor';

	case LIGHT_GREEN_BLUE = 'lightGcolor';

	case LIGHT_BLUE_COLOR = 'lightBcolor';

	case LIGHT_BRIGHTNESS = 'lightbright';

	case REMOTE_TYPE = 'remote_type';

	case COMMAND = 'cmd';

	case RF_CHANNEL = 'rfChl';

	case RED = 'colorR';

	case GREEN = 'colorG';

	case BLUE = 'colorB';

	case SENSITIVITY = 'sensitive';

	case BRIGHTNESS = 'bright';

	case BRIGHTNESS_2 = 'brightness';

	case BATTERY = 'battery';

	case LAST_UPDATE_TIME = 'lastUpdateTime';

	case ACTION_TIME = 'actionTime';

	case LIGHT_WITH_SCENES_TYPE = 'ltype';

	case PROTOCOL_VERSION = 'pVer';

	case SCENE_BRIGHTNESS = 'br';

	case SCENE_COLOR_TEMPERATURE = 'ct';

	case SCENE_COLOR_RED = 'r';

	case SCENE_COLOR_GREEN = 'g';

	case SCENE_COLOR_BLUE = 'b';

	case SCENE_NAME = 'name';

	case SCENE_COLOR_MODE = 'tf';

	case SCENE_SPEED_CHANGE = 'sp';

}
