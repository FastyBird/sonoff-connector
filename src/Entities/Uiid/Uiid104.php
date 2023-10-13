<?php declare(strict_types = 1);

/**
 * Uiid104.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           24.09.23
 */

namespace FastyBird\Connector\Sonoff\Entities\Uiid;

use FastyBird\Connector\Sonoff\Types;
use Orisai\ObjectMapper;
use function array_merge;

/**
 * UIID104 params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Uiid104 implements Entity
{

	use TDevice {
		TDevice::toStates as toDeviceState;
	}

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('sledOnline')]
		private readonly string|null $statusLed,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('fwVersion')]
		private readonly string|null $firmwareVersion,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('chipid')]
		private readonly string|null $chipId,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $ssid,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $rssi,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('pVer')]
		private readonly string|null $protocolVersion,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: LightWhiteState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly LightWhiteState $white,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: LightColorState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly LightColorState $color,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: LightModeState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly LightModeState $bright,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: LightModeState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly LightModeState $goodNight,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: LightModeState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly LightModeState $read,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: LightModeState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly LightModeState $nightLight,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: LightModeState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly LightModeState $party,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: LightModeState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly LightModeState $leisure,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: LightModeState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly LightModeState $soft,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: LightModeState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly LightModeState $colorful,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $switch,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue(
				['white', 'color', 'bright', 'goodNight', 'read', 'nightLight', 'party', 'leisure', 'soft', 'colorful'],
			),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('ltype')]
		private readonly string|null $type,
	)
	{
	}

	public function getStatusLed(): string|null
	{
		return $this->statusLed;
	}

	public function getFirmwareVersion(): string|null
	{
		return $this->firmwareVersion;
	}

	public function getChipId(): string|null
	{
		return $this->chipId;
	}

	public function getSsid(): string|null
	{
		return $this->ssid;
	}

	public function getRssi(): int|null
	{
		return $this->rssi;
	}

	public function getSwitch(): string|null
	{
		return $this->switch;
	}

	public function getType(): string|null
	{
		return $this->type;
	}

	public function getProtocolVersion(): string|null
	{
		return $this->protocolVersion;
	}

	public function getWhite(): LightWhiteState
	{
		return $this->white;
	}

	public function getColor(): LightColorState
	{
		return $this->color;
	}

	public function getBright(): LightModeState
	{
		return $this->bright;
	}

	public function getGoodNight(): LightModeState
	{
		return $this->goodNight;
	}

	public function getRead(): LightModeState
	{
		return $this->read;
	}

	public function getNightLight(): LightModeState
	{
		return $this->nightLight;
	}

	public function getParty(): LightModeState
	{
		return $this->party;
	}

	public function getLeisure(): LightModeState
	{
		return $this->leisure;
	}

	public function getSoft(): LightModeState
	{
		return $this->soft;
	}

	public function getColorful(): LightModeState
	{
		return $this->colorful;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'status_led' => $this->getStatusLed(),
			'firmware_version' => $this->getFirmwareVersion(),
			'chip_id' => $this->getChipId(),
			'ssid' => $this->getSsid(),
			'rssi' => $this->getRssi(),
			'switch' => $this->getSwitch(),
			'type' => $this->getType(),
			'protocol_version' => $this->getProtocolVersion(),
			'white' => $this->getWhite()->toArray(),
			'color' => $this->getColor()->toArray(),
			'bright' => $this->getBright()->toArray(),
			'good_night' => $this->getGoodNight()->toArray(),
			'read' => $this->getRead()->toArray(),
			'night_light' => $this->getNightLight()->toArray(),
			'party' => $this->getParty()->toArray(),
			'leisure' => $this->getLeisure()->toArray(),
			'soft' => $this->getSoft()->toArray(),
			'colorful' => $this->getColorful()->toArray(),
		];
	}

	public function toStates(): array
	{
		return array_merge(
			$this->toDeviceState(),
			[
				Types\ParameterType::CHANNEL => [
					[
						Types\PropertyParameter::NAME => Types\Parameter::SWITCH,
						Types\PropertyParameter::VALUE => $this->getSwitch(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::LIGHT_WITH_SCENES_TYPE,
						Types\PropertyParameter::VALUE => $this->getType(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::PROTOCOL_VERSION,
						Types\PropertyParameter::VALUE => $this->getProtocolVersion(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_BRIGHTNESS,
						Types\PropertyParameter::VALUE => $this->getWhite()->getBrightness(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_WHITE,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_TEMPERATURE,
						Types\PropertyParameter::VALUE => $this->getWhite()->getTemperature(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_WHITE,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_BRIGHTNESS,
						Types\PropertyParameter::VALUE => $this->getColor()->getBrightness(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_COLOR,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_RED,
						Types\PropertyParameter::VALUE => $this->getColor()->getRed(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_COLOR,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_GREEN,
						Types\PropertyParameter::VALUE => $this->getColor()->getGreen(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_COLOR,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_BLUE,
						Types\PropertyParameter::VALUE => $this->getColor()->getBlue(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_COLOR,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_BRIGHTNESS,
						Types\PropertyParameter::VALUE => $this->getBright()->getBrightness(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_BRIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_TEMPERATURE,
						Types\PropertyParameter::VALUE => $this->getBright()->getTemperature(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_BRIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_RED,
						Types\PropertyParameter::VALUE => $this->getBright()->getRed(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_BRIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_GREEN,
						Types\PropertyParameter::VALUE => $this->getBright()->getGreen(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_BRIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_BLUE,
						Types\PropertyParameter::VALUE => $this->getBright()->getBlue(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_BRIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_NAME,
						Types\PropertyParameter::VALUE => $this->getBright()->getName(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_BRIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_MODE,
						Types\PropertyParameter::VALUE => $this->getBright()->getColorMode(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_BRIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_SPEED_CHANGE,
						Types\PropertyParameter::VALUE => $this->getBright()->getSpeedChange(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_BRIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_BRIGHTNESS,
						Types\PropertyParameter::VALUE => $this->getGoodNight()->getBrightness(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_GOOD_NIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_TEMPERATURE,
						Types\PropertyParameter::VALUE => $this->getGoodNight()->getTemperature(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_GOOD_NIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_RED,
						Types\PropertyParameter::VALUE => $this->getGoodNight()->getRed(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_GOOD_NIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_GREEN,
						Types\PropertyParameter::VALUE => $this->getGoodNight()->getGreen(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_GOOD_NIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_BLUE,
						Types\PropertyParameter::VALUE => $this->getGoodNight()->getBlue(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_GOOD_NIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_NAME,
						Types\PropertyParameter::VALUE => $this->getGoodNight()->getName(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_GOOD_NIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_MODE,
						Types\PropertyParameter::VALUE => $this->getGoodNight()->getColorMode(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_GOOD_NIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_SPEED_CHANGE,
						Types\PropertyParameter::VALUE => $this->getGoodNight()->getSpeedChange(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_GOOD_NIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_BRIGHTNESS,
						Types\PropertyParameter::VALUE => $this->getRead()->getBrightness(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_READ,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_TEMPERATURE,
						Types\PropertyParameter::VALUE => $this->getRead()->getTemperature(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_READ,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_RED,
						Types\PropertyParameter::VALUE => $this->getRead()->getRed(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_READ,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_GREEN,
						Types\PropertyParameter::VALUE => $this->getRead()->getGreen(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_READ,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_BLUE,
						Types\PropertyParameter::VALUE => $this->getRead()->getBlue(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_READ,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_NAME,
						Types\PropertyParameter::VALUE => $this->getRead()->getName(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_READ,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_MODE,
						Types\PropertyParameter::VALUE => $this->getRead()->getColorMode(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_READ,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_SPEED_CHANGE,
						Types\PropertyParameter::VALUE => $this->getRead()->getSpeedChange(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_READ,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_BRIGHTNESS,
						Types\PropertyParameter::VALUE => $this->getNightLight()->getBrightness(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_NIGHT_LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_TEMPERATURE,
						Types\PropertyParameter::VALUE => $this->getNightLight()->getTemperature(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_NIGHT_LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_RED,
						Types\PropertyParameter::VALUE => $this->getNightLight()->getRed(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_NIGHT_LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_GREEN,
						Types\PropertyParameter::VALUE => $this->getNightLight()->getGreen(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_NIGHT_LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_BLUE,
						Types\PropertyParameter::VALUE => $this->getNightLight()->getBlue(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_NIGHT_LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_NAME,
						Types\PropertyParameter::VALUE => $this->getNightLight()->getName(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_NIGHT_LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_MODE,
						Types\PropertyParameter::VALUE => $this->getNightLight()->getColorMode(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_NIGHT_LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_SPEED_CHANGE,
						Types\PropertyParameter::VALUE => $this->getNightLight()->getSpeedChange(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_NIGHT_LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_BRIGHTNESS,
						Types\PropertyParameter::VALUE => $this->getParty()->getBrightness(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_PARTY,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_TEMPERATURE,
						Types\PropertyParameter::VALUE => $this->getParty()->getTemperature(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_PARTY,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_RED,
						Types\PropertyParameter::VALUE => $this->getParty()->getRed(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_PARTY,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_GREEN,
						Types\PropertyParameter::VALUE => $this->getParty()->getGreen(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_PARTY,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_BLUE,
						Types\PropertyParameter::VALUE => $this->getParty()->getBlue(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_PARTY,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_NAME,
						Types\PropertyParameter::VALUE => $this->getParty()->getName(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_PARTY,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_MODE,
						Types\PropertyParameter::VALUE => $this->getParty()->getColorMode(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_PARTY,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_SPEED_CHANGE,
						Types\PropertyParameter::VALUE => $this->getParty()->getSpeedChange(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_PARTY,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_BRIGHTNESS,
						Types\PropertyParameter::VALUE => $this->getLeisure()->getBrightness(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_LEISURE,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_TEMPERATURE,
						Types\PropertyParameter::VALUE => $this->getLeisure()->getTemperature(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_LEISURE,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_RED,
						Types\PropertyParameter::VALUE => $this->getLeisure()->getRed(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_LEISURE,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_GREEN,
						Types\PropertyParameter::VALUE => $this->getLeisure()->getGreen(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_LEISURE,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_BLUE,
						Types\PropertyParameter::VALUE => $this->getLeisure()->getBlue(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_LEISURE,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_NAME,
						Types\PropertyParameter::VALUE => $this->getLeisure()->getName(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_LEISURE,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_MODE,
						Types\PropertyParameter::VALUE => $this->getLeisure()->getColorMode(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_LEISURE,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_SPEED_CHANGE,
						Types\PropertyParameter::VALUE => $this->getLeisure()->getSpeedChange(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_LEISURE,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_BRIGHTNESS,
						Types\PropertyParameter::VALUE => $this->getSoft()->getBrightness(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_SOFT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_TEMPERATURE,
						Types\PropertyParameter::VALUE => $this->getSoft()->getTemperature(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_SOFT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_RED,
						Types\PropertyParameter::VALUE => $this->getSoft()->getRed(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_SOFT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_GREEN,
						Types\PropertyParameter::VALUE => $this->getSoft()->getGreen(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_SOFT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_BLUE,
						Types\PropertyParameter::VALUE => $this->getSoft()->getBlue(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_SOFT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_NAME,
						Types\PropertyParameter::VALUE => $this->getSoft()->getName(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_SOFT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_MODE,
						Types\PropertyParameter::VALUE => $this->getSoft()->getColorMode(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_SOFT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_SPEED_CHANGE,
						Types\PropertyParameter::VALUE => $this->getSoft()->getSpeedChange(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_SOFT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_BRIGHTNESS,
						Types\PropertyParameter::VALUE => $this->getColorful()->getBrightness(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_COLORFUL,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_TEMPERATURE,
						Types\PropertyParameter::VALUE => $this->getColorful()->getTemperature(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_COLORFUL,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_RED,
						Types\PropertyParameter::VALUE => $this->getColorful()->getRed(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_COLORFUL,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_GREEN,
						Types\PropertyParameter::VALUE => $this->getColorful()->getGreen(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_COLORFUL,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_BLUE,
						Types\PropertyParameter::VALUE => $this->getColorful()->getBlue(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_COLORFUL,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_NAME,
						Types\PropertyParameter::VALUE => $this->getColorful()->getName(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_COLORFUL,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_MODE,
						Types\PropertyParameter::VALUE => $this->getColorful()->getColorMode(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_COLORFUL,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_SPEED_CHANGE,
						Types\PropertyParameter::VALUE => $this->getColorful()->getSpeedChange(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_COLORFUL,
					],
				],
			],
		);
	}

}
