<?php declare(strict_types = 1);

/**
 * Uiid22.php
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
 * UIID22 params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Uiid22 implements Entity
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
		private readonly string|null $state,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 25, max: 255, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $channel0,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 25, max: 255, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $channel1,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 25, max: 255, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $channel2,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 25, max: 255, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $channel3,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 25, max: 255, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $channel4,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue(
				[self::TEMPERATURE_COLD, self::TEMPERATURE_MIDDLE, self::TEMPERATURE_WARM],
			),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $type,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 1, max: 6, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $mode,
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

	public function getState(): string|null
	{
		return $this->state;
	}

	public function getChannel0(): int|null
	{
		return $this->channel0;
	}

	public function getChannel1(): int|null
	{
		return $this->channel1;
	}

	public function getChannel2(): int|null
	{
		return $this->channel2;
	}

	public function getChannel3(): int|null
	{
		return $this->channel3;
	}

	public function getChannel4(): int|null
	{
		return $this->channel4;
	}

	public function getType(): string|null
	{
		return $this->type;
	}

	public function getMode(): int|null
	{
		return $this->mode;
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
			'state' => $this->getState(),
			'channel_0' => $this->getChannel0(),
			'channel_1' => $this->getChannel1(),
			'channel_2' => $this->getChannel2(),
			'channel_3' => $this->getChannel3(),
			'channel_4' => $this->getChannel4(),
			'type' => $this->getType(),
			'mode' => $this->getMode(),
		];
	}

	public function toStates(): array
	{
		return array_merge(
			$this->toDeviceState(),
			[
				Types\ParameterType::CHANNEL => [
					[
						Types\PropertyParameter::NAME => Types\Parameter::CHANNEL_0,
						Types\PropertyParameter::VALUE => $this->getChannel0(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::CHANNEL_1,
						Types\PropertyParameter::VALUE => $this->getChannel1(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::CHANNEL_2,
						Types\PropertyParameter::VALUE => $this->getChannel2(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::CHANNEL_3,
						Types\PropertyParameter::VALUE => $this->getChannel3(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::CHANNEL_4,
						Types\PropertyParameter::VALUE => $this->getChannel4(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::TYPE,
						Types\PropertyParameter::VALUE => $this->getType(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::MODE,
						Types\PropertyParameter::VALUE => $this->getMode(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
					],
				],
			],
		);
	}

}
