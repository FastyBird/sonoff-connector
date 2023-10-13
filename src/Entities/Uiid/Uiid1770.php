<?php declare(strict_types = 1);

/**
 * Uiid1770.php
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
 * UIID1770 params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Uiid1770 implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $temperature,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 10_000, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $humidity,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $battery,
	)
	{
	}

	public function getTemperature(): int|null
	{
		return $this->temperature;
	}

	public function getHumidity(): int|null
	{
		return $this->humidity;
	}

	public function getBattery(): int|null
	{
		return $this->battery;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'temperature' => $this->getTemperature(),
			'humidity' => $this->getHumidity(),
			'battery' => $this->getBattery(),
		];
	}

	public function toStates(): array
	{
		return array_merge(
			[
				Types\ParameterType::CHANNEL => [
					[
						Types\PropertyParameter::NAME => Types\Parameter::TEMPERATURE,
						Types\PropertyParameter::VALUE => $this->getTemperature(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SENSOR,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::HUMIDITY,
						Types\PropertyParameter::VALUE => $this->getHumidity(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SENSOR,
					],
					[
						Types\PropertyParameter::NAME => Types\Parameter::BATTERY,
						Types\PropertyParameter::VALUE => $this->getBattery(),
						Types\PropertyParameter::GROUP => Types\ParameterGroup::SENSOR,
					],
				],
			],
		);
	}

}
