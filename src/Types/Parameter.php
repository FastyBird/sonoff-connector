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

	public const STATUS_LED = 'sledOnline';

	public const FIRMWARE_VERSION = 'fwVersion';

	public const RSSI = 'rssi';

	public const SSID = 'ssid';

	public const BSSID = 'bssid';

	public const SWITCH = 'switch';

	public const STARTUP = 'startup';

	public const PULSE = 'pulse';

	public const PULSE_WIDTH = 'pulseWidth';

	public const MINIMUM_BRIGHTNESS = 'brightMin';

	public const MAXIMUM_BRIGHTNESS = 'brightMax';

	public const MODE = 'mode';

	public const POWER = 'power';

	public const VOLTAGE = 'voltage';

	public const CURRENT = 'current';

	public const CONSUMPTION = 'oneKwh';

	public const CONSUMPTION_DATA = 'oneKwhData';

	public const START_TIME = 'startTime';

	public const END_TIME = 'endTime';

	public const MAIN_SWITCH = 'mainSwitch';

	public const DEVICE_TYPE = 'deviceType';

	public const SENSOR_TYPE = 'sensorType';

	public const CURRENT_TEMPERATURE = 'currentTemperature';

	public const CURRENT_HUMIDITY = 'currentHumidity';

	public const CHANNEL_0 = 'channel0';

	public const CHANNEL_1 = 'channel1';

	public const CHANNEL_2 = 'channel2';

	public const CHANNEL_3 = 'channel3';

	public const CHANNEL_4 = 'channel4';

	public const TYPE = 'type';

	public const FAN = 'fan';

	public const SPEED = 'speed';

	public const SHAKE = 'shake';

	public const DUSTY = 'dusty';

	public const NOISE = 'noise';

	public const LIGHT = 'light';

	public const TEMPERATURE = 'temperature';

	public const HUMIDITY = 'humidity';

	public const STATE = 'state';

	public const WATER = 'water';

	public const LIGHT_TYPE = 'light_type';

	public const LIGHT_SWITCH = 'lightswitch';

	public const LIGHT_MODE = 'lightmode';

	public const LIGHT_RED_COLOR = 'lightRcolor';

	public const LIGHT_GREEN_BLUE = 'lightGcolor';

	public const LIGHT_BLUE_COLOR = 'lightBcolor';

	public const LIGHT_BRIGHTNESS = 'lightbright';

	public const REMOTE_TYPE = 'remote_type';

	public const COMMAND = 'cmd';

	public const RF_CHANNEL = 'rfChl';

	public const RED = 'colorR';

	public const GREEN = 'colorG';

	public const BLUE = 'colorB';

	public const SENSITIVITY = 'sensitive';

	public const BRIGHTNESS = 'bright';

	public const BRIGHTNESS_2 = 'brightness';

	public const BATTERY = 'battery';

	public const LAST_UPDATE_TIME = 'lastUpdateTime';

	public const ACTION_TIME = 'actionTime';

	public const LIGHT_WITH_SCENES_TYPE = 'ltype';

	public const PROTOCOL_VERSION = 'pVer';

	public const SCENE_BRIGHTNESS = 'br';

	public const SCENE_COLOR_TEMPERATURE = 'ct';

	public const SCENE_COLOR_RED = 'r';

	public const SCENE_COLOR_GREEN = 'g';

	public const SCENE_COLOR_BLUE = 'b';

	public const SCENE_NAME = 'name';

	public const SCENE_COLOR_MODE = 'tf';

	public const SCENE_SPEED_CHANGE = 'sp';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
