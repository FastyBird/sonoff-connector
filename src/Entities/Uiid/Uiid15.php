<?php declare(strict_types = 1);

/**
 * Uiid15.php
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
 * UIID15 params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Uiid15 implements Entity
{

	use TDevice {
		TDevice::toStates as toDeviceState;
	}

	public const DEVICE_TYPE_NORMAL = 'normal';

	public const DEVICE_TYPE_TEMPERATURE = 'temperature';

	public const DEVICE_TYPE_HUMIDITY = 'humidity';

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
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF, self::VALUE_STAY]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $startup,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $pulse,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 500, max: 3_600_000, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $pulseWidth,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $mainSwitch,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue(
				[self::DEVICE_TYPE_NORMAL, self::DEVICE_TYPE_TEMPERATURE, self::DEVICE_TYPE_HUMIDITY],
			),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $deviceType,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue(['DHT11', 'DS18B20', 'AM2301', 'MS01', 'errorType']),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $sensorType,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly float|null $currentHumidity,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly float|null $currentTemperature,
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

	public function getStartup(): string|null
	{
		return $this->startup;
	}

	public function getPulse(): string|null
	{
		return $this->pulse;
	}

	public function getPulseWidth(): int|null
	{
		return $this->pulseWidth;
	}

	public function getMainSwitch(): string|null
	{
		return $this->mainSwitch;
	}

	public function getDeviceType(): string|null
	{
		return $this->deviceType;
	}

	public function getSensorType(): string|null
	{
		return $this->sensorType;
	}

	public function getCurrentTemperature(): float|null
	{
		return $this->currentTemperature;
	}

	public function getCurrentHumidity(): float|null
	{
		return $this->currentHumidity;
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
			'startup' => $this->getStartup(),
			'pulse' => $this->getPulse(),
			'pulse_width' => $this->getPulseWidth(),
			'main_switch' => $this->getMainSwitch(),
			'device_type' => $this->getDeviceType(),
			'sensor_type' => $this->getSensorType(),
			'current_temperature' => $this->getCurrentTemperature(),
			'current_humidity' => $this->getCurrentHumidity(),
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
						Types\PropertyParameter::GROUP => Types\ParameterGroup::THERMOSTAT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::STARTUP,
						Types\PropertyParameter::VALUE => $this->getStartup(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::THERMOSTAT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::PULSE,
						Types\PropertyParameter::VALUE => $this->getPulse(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::THERMOSTAT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::PULSE_WIDTH,
						Types\PropertyParameter::VALUE => $this->getPulseWidth(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::THERMOSTAT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::MAIN_SWITCH,
						Types\PropertyParameter::VALUE => $this->getMainSwitch(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::THERMOSTAT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::DEVICE_TYPE,
						Types\PropertyParameter::VALUE => $this->getDeviceType(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::THERMOSTAT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::SENSOR_TYPE,
						Types\PropertyParameter::VALUE => $this->getSensorType(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::THERMOSTAT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::CURRENT_TEMPERATURE,
						Types\PropertyParameter::VALUE => $this->getCurrentTemperature(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::THERMOSTAT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::CURRENT_HUMIDITY,
						Types\PropertyParameter::VALUE => $this->getCurrentHumidity(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::THERMOSTAT,
					],
				],
			],
		);
	}

}
