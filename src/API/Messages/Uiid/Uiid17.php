<?php declare(strict_types = 1);

/**
 * Uiid17.php
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
 * UIID17 params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Uiid17 implements Uuid
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
		private readonly string|null $fan,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::SPEED_SLOW, self::SPEED_MODERATE, self::SPEED_FAST]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $speed,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::MODE_NORMAL, self::MODE_NATURAL, self::MODE_SLEEP]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $mode,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $shake,
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

	public function getFan(): string|null
	{
		return $this->fan;
	}

	public function getMode(): string|null
	{
		return $this->mode;
	}

	public function getSpeed(): string|null
	{
		return $this->speed;
	}

	public function getShake(): string|null
	{
		return $this->shake;
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
			'fan' => $this->getFan(),
			'speed' => $this->getSpeed(),
			'mode' => $this->getMode(),
			'shake' => $this->getShake(),
		];
	}

	public function toStates(): array
	{
		return array_merge(
			$this->toDeviceState(),
			[
				Types\ParameterType::CHANNEL->value => [
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::FAN->value,
						Types\PropertyParameter::VALUE->value => $this->getFan(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::FAN->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::SPEED->value,
						Types\PropertyParameter::VALUE->value => $this->getSpeed(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::FAN->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::MODE->value,
						Types\PropertyParameter::VALUE->value => $this->getMode(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::FAN->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::SHAKE->value,
						Types\PropertyParameter::VALUE->value => $this->getShake(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::FAN->value,
					],
				],
			],
		);
	}

}
