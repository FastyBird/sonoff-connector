<?php declare(strict_types = 1);

/**
 * Uiid29.php
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

use Orisai\ObjectMapper;
use function array_map;
use function array_merge;

/**
 * UIID29 params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Uiid29 implements Uuid
{

	use TDevice {
		TDevice::toStates as toDeviceState;
	}
	use TSwitches {
		TSwitches::toStates as toSwitchesState;
	}

	/**
	 * @param array<SwitchState> $switches
	 * @param array<SwitchConfiguration> $configuration
	 * @param array<SwitchPulse> $pulses
	 */
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
			new ObjectMapper\Rules\MappedObjectValue(class: SwitchState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly array $switches,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: SwitchConfiguration::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('configure')]
		private readonly array $configuration,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: SwitchPulse::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly array $pulses,
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

	/**
	 * @return array<SwitchState>
	 */
	public function getSwitches(): array
	{
		return $this->switches;
	}

	/**
	 * @return array<SwitchPulse>
	 */
	public function getPulses(): array
	{
		return $this->pulses;
	}

	/**
	 * @return array<SwitchConfiguration>
	 */
	public function getConfiguration(): array
	{
		return $this->configuration;
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
			'switches' => array_map(
				static fn (SwitchState $state): array => $state->toArray(),
				$this->getSwitches(),
			),
			'configuration' => array_map(
				static fn (SwitchConfiguration $state): array => $state->toArray(),
				$this->getConfiguration(),
			),
			'pulses' => array_map(
				static fn (SwitchPulse $state): array => $state->toArray(),
				$this->getPulses(),
			),
		];
	}

	public function toStates(): array
	{
		return array_merge(
			$this->toDeviceState(),
			$this->toSwitchesState(),
		);
	}

}
