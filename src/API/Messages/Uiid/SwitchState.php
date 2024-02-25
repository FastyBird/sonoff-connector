<?php declare(strict_types = 1);

/**
 * SwitchState.php
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

namespace FastyBird\Connector\Sonoff\API\Messages\Uiid;

use Orisai\ObjectMapper;

/**
 * Switch state entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SwitchState implements ObjectMapper\MappedObject
{

	private const VALUE_ON = 'on';

	private const VALUE_OFF = 'off';

	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [self::VALUE_ON, self::VALUE_OFF])]
		private readonly string $switch,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 10, unsigned: true, castNumericString: true)]
		private readonly int $outlet,
	)
	{
	}

	public function getSwitch(): string
	{
		return $this->switch;
	}

	public function getOutlet(): int
	{
		return $this->outlet;
	}

	/**
	 * @return array<string, int|string>
	 */
	public function toArray(): array
	{
		return [
			'switch' => $this->getSwitch(),
			'outlet' => $this->getOutlet(),
		];
	}

}
