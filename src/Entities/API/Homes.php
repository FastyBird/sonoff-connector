<?php declare(strict_types = 1);

/**
 * Homes.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           07.05.23
 */

namespace FastyBird\Connector\Sonoff\Entities\API;

use Nette;
use function array_map;

/**
 * User homes list entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Homes implements Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<Home> $familyList
	 */
	public function __construct(
		private readonly string $currentFamilyId,
		private readonly array $familyList,
	)
	{
	}

	public function getCurrentFamilyId(): string
	{
		return $this->currentFamilyId;
	}

	/**
	 * @return array<Home>
	 */
	public function getHomes(): array
	{
		return $this->familyList;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'current_family_id' => $this->getCurrentFamilyId(),
			'homes' => array_map(static fn (Home $home): array => $home->toArray(), $this->getHomes()),
		];
	}

}
