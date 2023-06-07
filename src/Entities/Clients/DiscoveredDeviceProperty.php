<?php declare(strict_types = 1);

/**
 * DiscoveredDeviceProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           15.05.23
 */

namespace FastyBird\Connector\Sonoff\Entities\Clients;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;

/**
 * Discovered cloud device entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DiscoveredDeviceProperty implements Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<int, string>|array<int, string|int|float|array<int, string|int|float>>|null $format
	 */
	public function __construct(
		private readonly string $group,
		private readonly string $identifier,
		private readonly string $name,
		private readonly string $type,
		private readonly MetadataTypes\DataType $dataType,
		private readonly array|null $format,
		private readonly bool $settable,
		private readonly bool $queryable,
		private readonly float|int|string|bool|null $value = null,
	)
	{
	}

	public function getGroup(): string
	{
		return $this->group;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getDataType(): MetadataTypes\DataType
	{
		return $this->dataType;
	}

	/**
	 * @return array<int, string>|array<int, string|int|float|array<int, string|int|float>>|null
	 */
	public function getFormat(): array|null
	{
		return $this->format;
	}

	public function isSettable(): bool
	{
		return $this->settable;
	}

	public function isQueryable(): bool
	{
		return $this->queryable;
	}

	public function getValue(): float|bool|int|string|null
	{
		return $this->value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'group' => $this->getGroup(),
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'type' => $this->getType(),
			'data_type' => $this->getDataType()->getValue(),
			'format' => $this->getFormat(),
			'settable' => $this->isSettable(),
			'queryable' => $this->isQueryable(),
			'value' => $this->getValue(),
		];
	}

}
