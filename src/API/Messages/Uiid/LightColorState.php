<?php declare(strict_types = 1);

/**
 * LightColorState.php
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
 * RGB color state entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class LightColorState implements ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(min: 1, max: 100, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('br')]
		private int $brightness,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('r')]
		private int $red,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('g')]
		private int $green,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('b')]
		private int $blue,
	)
	{
	}

	public function getBrightness(): int
	{
		return $this->brightness;
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

	/**
	 * @return array<string, int>
	 */
	public function toArray(): array
	{
		return [
			'brightness' => $this->getBrightness(),
			'red' => $this->getRed(),
			'green' => $this->getGreen(),
			'blue' => $this->getBlue(),
		];
	}

}
