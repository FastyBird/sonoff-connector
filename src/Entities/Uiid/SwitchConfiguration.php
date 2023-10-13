<?php declare(strict_types = 1);

/**
 * SwitchConfiguration.php
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
 * Switch configuration entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SwitchConfiguration implements Entity
{

	private const VALUE_ON = 'on';

	private const VALUE_OFF = 'off';

	private const VALUE_STAY = 'stay';

	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [self::VALUE_ON, self::VALUE_OFF, self::VALUE_STAY])]
		private readonly string $startup,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 10, unsigned: true, castNumericString: true)]
		private readonly int $outlet,
	)
	{
	}

	public function getStartup(): string
	{
		return $this->startup;
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
			'startup' => $this->getStartup(),
			'outlet' => $this->getOutlet(),
		];
	}

}
