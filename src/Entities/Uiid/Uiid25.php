<?php declare(strict_types = 1);

/**
 * Uiid25.php
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
 * UIID25 params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Uiid25 implements Entity
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
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $switch,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 1, max: 2, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $state,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(castBoolLike: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly bool|null $water,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(castBoolLike: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('lightswitch')]
		private readonly bool|null $lightSwitch,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 1, max: 3, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('lightmode')]
		private readonly int|null $lightMode,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('lightRcolor')]
		private readonly int|null $lightRedColor,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('lightGcolor')]
		private readonly int|null $lightGreenColor,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('lightBcolor')]
		private readonly int|null $lightBlueColor,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('lightbright')]
		private readonly int|null $lightBrightness,
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

	public function getState(): int|null
	{
		return $this->state;
	}

	public function getWater(): bool|null
	{
		return $this->water;
	}

	public function getLightSwitch(): bool|null
	{
		return $this->lightSwitch;
	}

	public function getLightMode(): int|null
	{
		return $this->lightMode;
	}

	public function getLightRedColor(): int|null
	{
		return $this->lightRedColor;
	}

	public function getLightGreenColor(): int|null
	{
		return $this->lightGreenColor;
	}

	public function getLightBlueColor(): int|null
	{
		return $this->lightBlueColor;
	}

	public function getLightBrightness(): int|null
	{
		return $this->lightBrightness;
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
			'state' => $this->getState(),
			'water' => $this->getWater(),
			'light_switch' => $this->getLightSwitch(),
			'light_mode' => $this->getLightMode(),
			'light_red_color' => $this->getLightRedColor(),
			'light_green_color' => $this->getLightGreenColor(),
			'light_blue_color' => $this->getLightBlueColor(),
			'light_brightness' => $this->getLightBrightness(),
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
						Types\PropertyParameter::GROUP => Types\ParameterGroup::OTHER,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::STATE,
						Types\PropertyParameter::VALUE => $this->getState(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::OTHER,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::WATER,
						Types\PropertyParameter::VALUE => $this->getWater(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::OTHER,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::LIGHT_SWITCH,
						Types\PropertyParameter::VALUE => $this->getLightSwitch(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::LIGHT_MODE,
						Types\PropertyParameter::VALUE => $this->getLightMode(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::LIGHT_RED_COLOR,
						Types\PropertyParameter::VALUE => $this->getLightRedColor(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::LIGHT_GREEN_BLUE,
						Types\PropertyParameter::VALUE => $this->getLightGreenColor(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::LIGHT_BLUE_COLOR,
						Types\PropertyParameter::VALUE => $this->getLightBlueColor(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::LIGHT_BRIGHTNESS,
						Types\PropertyParameter::VALUE => $this->getLightBrightness(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
					],
				],
			],
		);
	}

}
