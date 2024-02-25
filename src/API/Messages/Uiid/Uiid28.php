<?php declare(strict_types = 1);

/**
 * Uiid28.php
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
 * UIID28 params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Uiid28 implements Uuid
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
			new ObjectMapper\Rules\IntValue(min: 1, max: 6, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('remote_type')]
		private readonly int|null $remoteType,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue(
				[self::COMMAND_CAPTURE, self::COMMAND_CAPTURE_CANCEL, self::COMMAND_EDIT, self::COMMAND_TRANSMIT, self::COMMAND_TRIGGER],
			),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('cmd')]
		private readonly string|null $command,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('rfChl')]
		private readonly int|null $rfChannel,
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

	public function getRemoteType(): int|null
	{
		return $this->remoteType;
	}

	public function getCommand(): string|null
	{
		return $this->command;
	}

	public function getRfChannel(): int|null
	{
		return $this->rfChannel;
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
			'remote_type' => $this->getRemoteType(),
			'command' => $this->getCommand(),
			'rf_channel' => $this->getRfChannel(),
		];
	}

	public function toStates(): array
	{
		return array_merge(
			$this->toDeviceState(),
			[
				Types\ParameterType::CHANNEL->value => [
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::REMOTE_TYPE->value,
						Types\PropertyParameter::VALUE->value => $this->getRemoteType(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::BRIDGE->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::COMMAND->value,
						Types\PropertyParameter::VALUE->value => $this->getCommand(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::BRIDGE->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::RF_CHANNEL->value,
						Types\PropertyParameter::VALUE->value => $this->getRfChannel(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::BRIDGE->value,
					],
				],
			],
		);
	}

}
