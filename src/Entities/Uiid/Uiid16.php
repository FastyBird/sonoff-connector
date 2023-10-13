<?php declare(strict_types = 1);

/**
 * Uiid16.php
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
 * UIID16 params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Uiid16 implements Entity
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
			new ObjectMapper\Rules\ArrayEnumValue(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				['0', '25', '38', '40', '61', '85', '103', '117', '130', '141', '150', '159', '167', '174', '180', '186', '192', '197', '202', '207', '211', '255'],
			),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $channel0,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				['0', '25', '38', '40', '61', '85', '103', '117', '130', '141', '150', '159', '167', '174', '180', '186', '192', '197', '202', '207', '211', '255'],
			),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $channel1,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue(
				[self::TEMPERATURE_COLD, self::TEMPERATURE_MIDDLE, self::TEMPERATURE_WARM],
			),
			new ObjectMapper\Rules\NullValue(),
		])]
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

	public function getState(): string|null
	{
		return $this->state;
	}

	public function getChannel0(): string|null
	{
		return $this->channel0;
	}

	public function getChannel1(): string|null
	{
		return $this->channel1;
	}

	public function getType(): string|null
	{
		return $this->type;
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
			'type' => $this->getType(),
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
						Types\PropertyParameter::NAME => Types\Parameter::TYPE,
						Types\PropertyParameter::VALUE => $this->getType(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::LIGHT,
					],
				],
			],
		);
	}

}
