<?php declare(strict_types = 1);

/**
 * Uiid103.php
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
 * UIID103 params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Uiid103 implements Entity
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
			new ObjectMapper\Rules\MappedObjectValue(class: LightWhiteState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly LightWhiteState $bright,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: LightWhiteState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly LightWhiteState $read,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: LightWhiteState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly LightWhiteState $computer,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: LightWhiteState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly LightWhiteState $nightLight,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $switch,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue(['white', 'bright', 'read', 'computer', 'nightLight']),
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

	public function getBright(): LightWhiteState
	{
		return $this->bright;
	}

	public function getRead(): LightWhiteState
	{
		return $this->read;
	}

	public function getComputer(): LightWhiteState
	{
		return $this->computer;
	}

	public function getNightLight(): LightWhiteState
	{
		return $this->nightLight;
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
			'bright' => $this->getBright()->toArray(),
			'read' => $this->getRead()->toArray(),
			'computer' => $this->getComputer()->toArray(),
			'night_light' => $this->getNightLight()->toArray(),
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
						Types\PropertyParameter::VALUE => $this->getBright()->getBrightness(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_BRIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_TEMPERATURE,
						Types\PropertyParameter::VALUE => $this->getBright()->getTemperature(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_BRIGHT,
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
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_BRIGHTNESS,
						Types\PropertyParameter::VALUE => $this->getComputer()->getBrightness(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_COMPUTER,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SCENE_COLOR_TEMPERATURE,
						Types\PropertyParameter::VALUE => $this->getComputer()->getTemperature(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SCENE_COMPUTER,
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
				],
			],
		);
	}

}
