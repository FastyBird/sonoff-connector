<?php declare(strict_types = 1);

/**
 * Uiid33.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           24.09.23
 */

namespace FastyBird\Connector\Sonoff\API\Messages\Uiid;

use FastyBird\Connector\Sonoff\Types;
use Orisai\ObjectMapper;
use function array_merge;

/**
 * UIID33 params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Uiid33 implements Uuid
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
		#[ObjectMapper\Modifiers\FieldName('light_type')]
		private readonly string|null $type,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 10, max: 100, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('bright')]
		private readonly string|null $brightness,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('colorR')]
		private readonly string|null $red,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('colorG')]
		private readonly string|null $green,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('colorB')]
		private readonly string|null $blue,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 1, max: 3, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $mode,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $speed,
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

	public function getBrightness(): string|null
	{
		return $this->brightness;
	}

	public function getRed(): string|null
	{
		return $this->red;
	}

	public function getGreen(): string|null
	{
		return $this->green;
	}

	public function getBlue(): string|null
	{
		return $this->blue;
	}

	public function getMode(): string|null
	{
		return $this->mode;
	}

	public function getSpeed(): string|null
	{
		return $this->speed;
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
			'brightness' => $this->getBrightness(),
			'red' => $this->getRed(),
			'green' => $this->getGreen(),
			'blue' => $this->getBlue(),
			'mode' => $this->getMode(),
			'speed' => $this->getSpeed(),
		];
	}

	public function toStates(): array
	{
		return array_merge(
			$this->toDeviceState(),
			[
				Types\ParameterType::CHANNEL->value => [
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::SWITCH->value,
						Types\PropertyParameter::VALUE->value => $this->getSwitch(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::TYPE->value,
						Types\PropertyParameter::VALUE->value => $this->getType(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::BRIGHTNESS->value,
						Types\PropertyParameter::VALUE->value => $this->getBrightness(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::RED->value,
						Types\PropertyParameter::VALUE->value => $this->getRed(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::GREEN->value,
						Types\PropertyParameter::VALUE->value => $this->getGreen(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::BLUE->value,
						Types\PropertyParameter::VALUE->value => $this->getBlue(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::MODE->value,
						Types\PropertyParameter::VALUE->value => $this->getMode(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::SPEED->value,
						Types\PropertyParameter::VALUE->value => $this->getSpeed(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
					],
				],
			],
		);
	}

}
