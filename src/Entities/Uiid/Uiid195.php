<?php declare(strict_types = 1);

/**
 * Uiid195.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           12.10.23
 */

namespace FastyBird\Connector\Sonoff\Entities\Uiid;

use FastyBird\Connector\Sonoff\Types;
use Orisai\ObjectMapper;
use function array_merge;

/**
 * UIID195 params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Uiid195 implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('fwVersion')]
		private readonly string|null $firmwareVersion,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly float|null $temperature,
	)
	{
	}

	public function getFirmwareVersion(): string|null
	{
		return $this->firmwareVersion;
	}

	public function getTemperature(): float|null
	{
		return $this->temperature;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'firmware_version' => $this->getFirmwareVersion(),
			'temperature' => $this->getTemperature(),
		];
	}

	public function toStates(): array
	{
		return array_merge(
			[
				Types\ParameterType::DEVICE => [
					[
						Types\PropertyParameter::NAME => Types\Parameter::FIRMWARE_VERSION,
						Types\PropertyParameter::VALUE => $this->getFirmwareVersion(),
					],
				],
			],
			[
				Types\ParameterType::CHANNEL => [
					[
						Types\PropertyParameter::NAME => Types\Parameter::TEMPERATURE,
						Types\PropertyParameter::VALUE => $this->getTemperature(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SENSOR,
					],
				],
			],
		);
	}

}
