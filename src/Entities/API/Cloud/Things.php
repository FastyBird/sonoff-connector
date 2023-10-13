<?php declare(strict_types = 1);

/**
 * Things.php
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
 * User things list for home entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Things implements Entities\API\Entity
{

	/**
	 * @param array<Device> $devices
	 * @param array<Group> $groups
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(Device::class),
		)]
		private readonly array $devices,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(Group::class),
		)]
		private readonly array $groups,
	)
	{
	}

	/**
	 * @return array<Device>
	 */
	public function getDevices(): array
	{
		return $this->devices;
	}

	/**
	 * @return array<Group>
	 */
	public function getGroups(): array
	{
		return $this->groups;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'devices' => array_map(static fn (Device $device): array => $device->toArray(), $this->getDevices()),
			'groups' => array_map(static fn (Group $group): array => $group->toArray(), $this->getGroups()),
		];
	}

}
