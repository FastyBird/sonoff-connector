<?php declare(strict_types = 1);

/**
 * Uiid102.php
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
 * UIID102 params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Uiid102 implements Uuid
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
			new ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $battery,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 1, max: 5, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $type,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $lastUpdateTime,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $actionTime,
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

	public function getBattery(): string|null
	{
		return $this->battery;
	}

	public function getType(): string|null
	{
		return $this->type;
	}

	public function getLastUpdateTime(): string|null
	{
		return $this->lastUpdateTime;
	}

	public function getActionTime(): string|null
	{
		return $this->actionTime;
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
			'battery' => $this->getBattery(),
			'type' => $this->getType(),
			'last_update_time' => $this->getLastUpdateTime(),
			'action_time' => $this->getActionTime(),
		];
	}

	public function toStates(): array
	{
		return array_merge(
			array_merge(
				$this->toDeviceState(),
				[
					Types\ParameterType::DEVICE->value => [
						[
							Types\PropertyParameter::NAME->value => Types\Parameter::BATTERY->value,
							Types\PropertyParameter::VALUE->value => $this->getBattery(),
						],
					],
				],
			),
			[
				Types\ParameterType::CHANNEL->value => [
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::SWITCH->value,
						Types\PropertyParameter::VALUE->value => $this->getSwitch(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SENSOR->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::TYPE->value,
						Types\PropertyParameter::VALUE->value => $this->getType(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SENSOR->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::LAST_UPDATE_TIME->value,
						Types\PropertyParameter::VALUE->value => $this->getLastUpdateTime(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SENSOR->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::ACTION_TIME->value,
						Types\PropertyParameter::VALUE->value => $this->getActionTime(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SENSOR->value,
					],
				],
			],
		);
	}

}
