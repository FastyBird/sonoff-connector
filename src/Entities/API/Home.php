<?php declare(strict_types = 1);

/**
 * Home.php
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
 * User home entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Home implements Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<Room> $roomList
	 */
	public function __construct(
		private readonly string $id,
		private readonly string $apikey,
		private readonly string $name,
		private readonly int $index,
		private readonly array $roomList = [],
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getApikey(): string
	{
		return $this->apikey;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getIndex(): int
	{
		return $this->index;
	}

	/**
	 * @return array<Room>
	 */
	public function getRooms(): array
	{
		return $this->roomList;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'api_key' => $this->getApikey(),
			'name' => $this->getName(),
			'index' => $this->getIndex(),
			'rooms' => array_map(static fn (Room $room): array => $room->toArray(), $this->getRooms()),
		];
	}

}
