<?php declare(strict_types = 1);

/**
 * LightModeState.php
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

use FastyBird\Connector\Sonoff\Entities\API\Entity;
use Orisai\ObjectMapper;

/**
 * Mode state entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class LightModeState implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(min: 1, max: 100, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('br')]
		private readonly int $brightness,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('ct')]
		private readonly int $temperature,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('r')]
		private readonly int $red,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('g')]
		private readonly int $green,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('b')]
		private readonly int $blue,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly int $name,
		#[ObjectMapper\Rules\IntValue(min: 1, max: 4, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('tf')]
		private readonly int $colorMode,
		#[ObjectMapper\Rules\IntValue(min: 1, max: 100, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('sp')]
		private readonly int $speedChange,
	)
	{
	}

	public function getBrightness(): int
	{
		return $this->brightness;
	}

	public function getTemperature(): int
	{
		return $this->temperature;
	}

	public function getRed(): int
	{
		return $this->red;
	}

	public function getGreen(): int
	{
		return $this->green;
	}

	public function getBlue(): int
	{
		return $this->blue;
	}

	public function getName(): int
	{
		return $this->name;
	}

	public function getColorMode(): int
	{
		return $this->colorMode;
	}

	public function getSpeedChange(): int
	{
		return $this->speedChange;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'brightness' => $this->getBrightness(),
			'temperature' => $this->getTemperature(),
			'red' => $this->getRed(),
			'green' => $this->getGreen(),
			'blue' => $this->getBlue(),
			'name' => $this->getName(),
			'color_mode' => $this->getColorMode(),
			'speed_change' => $this->getSpeedChange(),
		];
	}

}
