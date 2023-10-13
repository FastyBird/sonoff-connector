<?php declare(strict_types = 1);

/**
 * Family.php
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

namespace FastyBird\Connector\Sonoff\Entities\API\Cloud;

use FastyBird\Connector\Sonoff\Entities;
use Orisai\ObjectMapper;
use function array_map;

/**
 * User homes list entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Family implements Entities\API\Entity
{

	/**
	 * @param array<Home> $homes
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('currentFamilyId')]
		private readonly string $familyId,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(Home::class),
		)]
		#[ObjectMapper\Modifiers\FieldName('familyList')]
		private readonly array $homes,
	)
	{
	}

	public function getFamilyId(): string
	{
		return $this->familyId;
	}

	/**
	 * @return array<Home>
	 */
	public function getHomes(): array
	{
		return $this->homes;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'family_id' => $this->getFamilyId(),
			'homes' => array_map(static fn (Home $home): array => $home->toArray(), $this->getHomes()),
		];
	}

}
