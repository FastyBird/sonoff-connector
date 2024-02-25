<?php declare(strict_types = 1);

/**
 * Uiid32.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           18.09.23
 */

namespace FastyBird\Connector\Sonoff\API\Messages\Uiid;

use FastyBird\Connector\Sonoff\Types;
use Orisai\ObjectMapper;
use function array_merge;

/**
 * UIID32 params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Uiid32 implements Uuid
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
			new ObjectMapper\Rules\FloatValue(castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly float|null $power,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly float|null $voltage,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly float|null $current,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_START, self::VALUE_STOP, self::VALUE_GET]),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('oneKwh')]
		private readonly string|null $consumption,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $startTime,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $endTime,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('oneKwhData')]
		private readonly float|null $consumptionData,
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

	public function getPower(): float|null
	{
		return $this->power;
	}

	public function getVoltage(): float|null
	{
		return $this->voltage;
	}

	public function getCurrent(): float|null
	{
		return $this->current;
	}

	public function getConsumption(): string|null
	{
		return $this->consumption;
	}

	public function getConsumptionData(): float|null
	{
		return $this->consumptionData;
	}

	public function getStartTime(): string|null
	{
		return $this->startTime;
	}

	public function getEndTime(): string|null
	{
		return $this->endTime;
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
			'power' => $this->getPower(),
			'voltage' => $this->getVoltage(),
			'current' => $this->getCurrent(),
			'consumption' => $this->getConsumption(),
			'consumption_data' => $this->getConsumptionData(),
			'start_time' => $this->getStartTime(),
			'end_time' => $this->getEndTime(),
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
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::POWER->value,
						Types\PropertyParameter::VALUE->value => $this->getPower(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::VOLTAGE->value,
						Types\PropertyParameter::VALUE->value => $this->getVoltage(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::CURRENT->value,
						Types\PropertyParameter::VALUE->value => $this->getCurrent(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::CONSUMPTION->value,
						Types\PropertyParameter::VALUE->value => $this->getConsumption(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::CONSUMPTION_DATA->value,
						Types\PropertyParameter::VALUE->value => $this->getConsumptionData(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::START_TIME->value,
						Types\PropertyParameter::VALUE->value => $this->getStartTime(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::END_TIME->value,
						Types\PropertyParameter::VALUE->value => $this->getEndTime(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
					],
				],
			],
		);
	}

}
