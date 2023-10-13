<?php declare(strict_types = 1);

/**
 * DeviceEventData.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           21.09.23
 */

namespace FastyBird\Connector\Sonoff\Entities\API\Lan;

use FastyBird\Connector\Sonoff\Entities;
use Orisai\ObjectMapper;
use function array_map;

/**
 * Device info entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceEventData implements Entities\API\Entity
{

	private const VALUE_ON = 'on';

	private const VALUE_OFF = 'off';

	private const VALUE_STAY = 'stay';

	/**
	 * @param array<SwitchState> $switchesStates
	 */
	public function __construct(
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
		private readonly string|null $ssid = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $rssi = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $bssid = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $signalStrength = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('sledOnline')]
		private readonly int|null $statusLed = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $switch = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF, self::VALUE_STAY]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $startup = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $pulse = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(500, 3_599_500, true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $pulseWidth = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(0, 255, true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $mode = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(0, 100, true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $brightness = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(0, 254, true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('brightmin')]
		private readonly int|null $minimumBrightness = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(1, 255, true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('brightmax')]
		private readonly int|null $maximumBrightness = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('subDevId')]
		private readonly string|null $subDeviceId = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: SwitchState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('switches')]
		private readonly array $switchesStates = [],
	)
	{
	}

	public function isSwitch(): bool
	{
		return $this->getSwitch() !== null && $this->getBrightness() === null;
	}

	public function isSwitches(): bool
	{
		return $this->getSwitchesStates() !== [];
	}

	public function isLight(): bool
	{
		return $this->getBrightness() !== null;
	}

	public function getSsid(): string|null
	{
		return $this->ssid;
	}

	public function getRssi(): int|null
	{
		return $this->rssi ?? $this->signalStrength;
	}

	public function getFirmwareVersion(): string|null
	{
		return $this->firmwareVersion;
	}

	public function getBssid(): string|null
	{
		return $this->bssid;
	}

	public function getStatusLed(): int|null
	{
		return $this->statusLed;
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

	public function getMode(): int|null
	{
		return $this->mode;
	}

	public function getBrightness(): int|null
	{
		return $this->brightness;
	}

	public function getMinimumBrightness(): int|null
	{
		return $this->minimumBrightness;
	}

	public function getMaximumBrightness(): int|null
	{
		return $this->maximumBrightness;
	}

	public function getSubDeviceId(): string|null
	{
		return $this->subDeviceId;
	}

	/**
	 * @return array<SwitchState>
	 */
	public function getSwitchesStates(): array
	{
		return $this->switchesStates;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'rssi' => $this->getRssi(),
			'ssid' => $this->getSsid(),
			'bssid' => $this->getBssid(),
			'firmware_version' => $this->getFirmwareVersion(),
			'status_led' => $this->getStatusLed(),
			// Device specific
			'switch' => $this->getSwitch(),
			'startup' => $this->getStartup(),
			'pulse' => $this->getPulse(),
			'pulse_width' => $this->getPulseWidth(),
			'brightness' => $this->getBrightness(),
			'minimum_brightness' => $this->getMinimumBrightness(),
			'maximum_brightness' => $this->getMaximumBrightness(),
			'mode' => $this->getMode(),
			'sub_device_id' => $this->getSubDeviceId(),
			'switches' => [
				'states' => array_map(
					static fn (SwitchState $state): array => $state->toArray(),
					$this->getSwitchesStates(),
				),
			],
		];
	}

}
