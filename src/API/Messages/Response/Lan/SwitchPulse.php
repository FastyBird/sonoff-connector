<?php declare(strict_types = 1);

/**
 * SwitchPulse.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           21.09.23
 */

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Lan;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;

/**
 * Switch pulse configuration entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SwitchPulse implements API\Messages\Message
{

	private const VALUE_ON = 'on';

	private const VALUE_OFF = 'off';

	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [self::VALUE_ON, self::VALUE_OFF])]
		private readonly string $pulse,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [self::VALUE_ON, self::VALUE_OFF])]
		private readonly string $switch,
		#[ObjectMapper\Rules\IntValue(min: 500, max: 3_599_500, unsigned: true)]
		private readonly int $width,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 10, unsigned: true)]
		private readonly int $outlet,
	)
	{
	}

	public function getPulse(): string
	{
		return $this->pulse;
	}

	public function getSwitch(): string
	{
		return $this->switch;
	}

	public function getWidth(): int
	{
		return $this->width;
	}

	public function getOutlet(): int
	{
		return $this->outlet;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'pulse' => $this->getPulse(),
			'switch' => $this->getSwitch(),
			'width' => $this->getWidth(),
			'outlet' => $this->getOutlet(),
		];
	}

}
