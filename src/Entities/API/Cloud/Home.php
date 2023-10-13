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

namespace FastyBird\Connector\Sonoff\Entities\API\Cloud;

use FastyBird\Connector\Sonoff\Entities;
use Orisai\ObjectMapper;
use function array_map;

/**
 * User home entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Home implements Entities\API\Entity
{

	/**
	 * @param array<Room> $rooms
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('apikey')]
		private readonly string $apiKey,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $name,
		#[ObjectMapper\Rules\IntValue()]
		private readonly int $index,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(Room::class),
		)]
		#[ObjectMapper\Modifiers\FieldName('roomList')]
		private readonly array $rooms = [],
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getApikey(): string
	{
		return $this->apiKey;
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
		return $this->rooms;
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
