<?php declare(strict_types = 1);

/**
 * Uiid19.php
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
 * UIID19 params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Uiid19 implements Uuid
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
			new ObjectMapper\Rules\ArrayEnumValue([self::MODE_NORMAL]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $mode,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 1, max: 3, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $state,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(castBoolLike: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly bool|null $water,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 50, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $temperature,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 20, max: 90, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $humidity,
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

	public function getMode(): string|null
	{
		return $this->mode;
	}

	public function getWater(): bool|null
	{
		return $this->water;
	}

	public function getTemperature(): int|null
	{
		return $this->temperature;
	}

	public function getHumidity(): int|null
	{
		return $this->humidity;
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
			'mode' => $this->getMode(),
			'water' => $this->getWater(),
			'temperature' => $this->getTemperature(),
			'humidity' => $this->getHumidity(),
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
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::HUMIDIFIER->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::STATE->value,
						Types\PropertyParameter::VALUE->value => $this->getState(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::HUMIDIFIER->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::MODE->value,
						Types\PropertyParameter::VALUE->value => $this->getMode(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::HUMIDIFIER->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::WATER->value,
						Types\PropertyParameter::VALUE->value => $this->getWater(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::HUMIDIFIER->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::TEMPERATURE->value,
						Types\PropertyParameter::VALUE->value => $this->getTemperature(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::HUMIDIFIER->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::HUMIDITY->value,
						Types\PropertyParameter::VALUE->value => $this->getHumidity(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::HUMIDIFIER->value,
					],
				],
			],
		);
	}

}
