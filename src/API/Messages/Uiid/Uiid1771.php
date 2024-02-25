<?php declare(strict_types = 1);

/**
 * Uiid1771.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           12.10.23
 */

namespace FastyBird\Connector\Sonoff\API\Messages\Uiid;

use FastyBird\Connector\Sonoff\Types;
use Orisai\ObjectMapper;
use function array_merge;

/**
 * UIID1771 params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class Uiid1771 implements Uuid
{

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private int|null $temperature,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 10_000, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private int|null $humidity,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private int|null $battery,
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
				Types\ParameterType::CHANNEL->value => [
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::TEMPERATURE->value,
						Types\PropertyParameter::VALUE->value => $this->getTemperature(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SENSOR->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::HUMIDITY->value,
						Types\PropertyParameter::VALUE->value => $this->getHumidity(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SENSOR->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::BATTERY->value,
						Types\PropertyParameter::VALUE->value => $this->getBattery(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SENSOR->value,
					],
				],
			],
		);
	}

}
