<?php declare(strict_types = 1);

/**
 * Home.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           07.05.23
 */

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Cloud;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;
use function array_map;

/**
 * User home entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class Home implements API\Messages\Message
{

	/**
	 * @param array<Room> $rooms
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('apikey')]
		private string $apiKey,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $name,
		#[ObjectMapper\Rules\IntValue()]
		private int $index,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(Room::class),
		)]
		#[ObjectMapper\Modifiers\FieldName('roomList')]
		private array $rooms = [],
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
