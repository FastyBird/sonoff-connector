<?php declare(strict_types = 1);

/**
 * LightModeState.php
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

/**
 * Mode state entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class LightModeState implements ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(min: 1, max: 100, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('br')]
		private int $brightness,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('ct')]
		private int $temperature,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('r')]
		private int $red,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('g')]
		private int $green,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('b')]
		private int $blue,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private int $name,
		#[ObjectMapper\Rules\IntValue(min: 1, max: 4, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('tf')]
		private int $colorMode,
		#[ObjectMapper\Rules\IntValue(min: 1, max: 100, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('sp')]
		private int $speedChange,
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
	 * @return array<string, int>
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
