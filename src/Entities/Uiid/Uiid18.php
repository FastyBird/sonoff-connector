<?php declare(strict_types = 1);

/**
 * Uiid18.php
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
 * UIID18 params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Uiid18 implements Entity
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
			new ObjectMapper\Rules\IntValue(min: 1, max: 10, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $dusty,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 1, max: 10, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $noise,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 1, max: 10, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $light,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly float|null $temperature,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 1, max: 100, unsigned: true, castNumericString: true),
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

	public function getDusty(): int|null
	{
		return $this->dusty;
	}

	public function getNoise(): int|null
	{
		return $this->noise;
	}

	public function getLight(): int|null
	{
		return $this->light;
	}

	public function getTemperature(): float|null
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
			'dusty' => $this->getDusty(),
			'noise' => $this->getNoise(),
			'light' => $this->getLight(),
			'temperature' => $this->getTemperature(),
			'humidity' => $this->getHumidity(),
		];
	}

	public function toStates(): array
	{
		return array_merge(
			$this->toDeviceState(),
			[
				Types\ParameterType::CHANNEL => [
					[
						Types\PropertyParameter::NAME => Types\Parameter::DUSTY,
						Types\PropertyParameter::VALUE => $this->getDusty(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SENSOR,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::NOISE,
						Types\PropertyParameter::VALUE => $this->getNoise(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SENSOR,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::LIGHT,
						Types\PropertyParameter::VALUE => $this->getLight(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SENSOR,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::TEMPERATURE,
						Types\PropertyParameter::VALUE => $this->getTemperature(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SENSOR,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::HUMIDITY,
						Types\PropertyParameter::VALUE => $this->getHumidity(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SENSOR,
					],
				],
			],
		);
	}

}
