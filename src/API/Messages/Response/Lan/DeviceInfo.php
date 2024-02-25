<?php declare(strict_types = 1);

/**
 * DeviceInfo.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           15.09.23
 */

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Lan;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;
use function array_map;

/**
 * Device info entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceInfo implements API\Messages\Message
{

	private const VALUE_ON = 'on';

	private const VALUE_OFF = 'off';

	private const VALUE_STAY = 'stay';

	/**
	 * @param array<SwitchState> $switchesStates
	 * @param array<SwitchConfiguration> $switchesConfiguration
	 * @param array<SwitchPulse> $switchesPulses
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('deviceid')]
		private readonly string $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $ssid,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('otaUnlock')]
		private readonly bool $otaEnabled,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('fwVersion')]
		private readonly string $firmwareVersion,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('bssid')]
		private readonly string $bssid,
		#[ObjectMapper\Rules\IntValue()]
		#[ObjectMapper\Modifiers\FieldName('signalStrength')]
		private readonly int $rssi,
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
			new ObjectMapper\Rules\IntValue(0, 100, true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $brightness = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(0, 255, true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $mode = null,
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
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('sledOnline')]
		private readonly string|null $statusLed = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: SwitchState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('switches')]
		private readonly array $switchesStates = [],
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: SwitchConfiguration::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('configure')]
		private readonly array $switchesConfiguration = [],
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: SwitchPulse::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('pulses')]
		private readonly array $switchesPulses = [],
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getSsid(): string
	{
		return $this->ssid;
	}

	public function getRssi(): int
	{
		return $this->rssi;
	}

	public function getBssid(): string
	{
		return $this->bssid;
	}

	public function getFirmwareVersion(): string
	{
		return $this->firmwareVersion;
	}

	public function hasOtaEnabled(): bool
	{
		return $this->otaEnabled;
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

	public function getSwitch(): bool|null
	{
		return $this->switch === null ? null : $this->switch === self::VALUE_ON;
	}

	public function getStartup(): bool|null
	{
		return $this->startup === null ? null : $this->startup === self::VALUE_ON;
	}

	public function getPulse(): bool|null
	{
		return $this->pulse === null ? null : $this->pulse === self::VALUE_ON;
	}

	public function getPulseWidth(): int|null
	{
		return $this->pulseWidth;
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

	public function getMode(): int|null
	{
		return $this->mode;
	}

	public function getStatusLed(): bool|null
	{
		return $this->statusLed === null ? null : $this->statusLed === self::VALUE_ON;
	}

	/**
	 * @return array<SwitchState>
	 */
	public function getSwitchesStates(): array
	{
		return $this->switchesStates;
	}

	/**
	 * @return array<SwitchConfiguration>
	 */
	public function getSwitchesConfiguration(): array
	{
		return $this->switchesConfiguration;
	}

	/**
	 * @return array<SwitchPulse>
	 */
	public function getSwitchesPulses(): array
	{
		return $this->switchesPulses;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'device_id' => $this->getId(),
			'ssid' => $this->getSsid(),
			'bssid' => $this->getBssid(),
			'rssi' => $this->getRssi(),
			'firmware_version' => $this->getFirmwareVersion(),
			'ota_enabled' => $this->hasOtaEnabled(),
			// Device specific
			'switch' => $this->getSwitch(),
			'startup' => $this->getStartup(),
			'pulse' => $this->getPulse(),
			'pulse_width' => $this->getPulseWidth(),
			'brightness' => $this->getBrightness(),
			'minimum_brightness' => $this->getMinimumBrightness(),
			'maximum_brightness' => $this->getMaximumBrightness(),
			'mode' => $this->getMode(),
			'status_led' => $this->getStatusLed(),
			'switches' => [
				'states' => array_map(
					static fn (SwitchState $state): array => $state->toArray(),
					$this->getSwitchesStates(),
				),
				'configuration' => array_map(
					static fn (SwitchConfiguration $state): array => $state->toArray(),
					$this->getSwitchesConfiguration(),
				),
				'pulses' => array_map(
					static fn (SwitchPulse $state): array => $state->toArray(),
					$this->getSwitchesPulses(),
				),
			],
		];
	}

}
