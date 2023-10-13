<?php declare(strict_types = 1);

/**
 * Group.php
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
use Nette\Utils;
use Orisai\ObjectMapper;
use function array_map;

/**
 * User home group entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Group implements Entities\API\Entity
{

	/**
	 * @param array<string> $denyFeatures
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $name,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $mainDeviceId,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ObjectValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly Entities\Uiid\Entity|null $state = null,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\StringValue(notEmpty: true),
		)]
		private readonly array $denyFeatures = [],
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getMainDeviceId(): string
	{
		return $this->mainDeviceId;
	}

	public function getState(): Entities\Uiid\Entity|null
	{
		return $this->state;
	}

	/**
	 * @return array<string>
	 */
	public function getDenyFeatures(): array
	{
		return array_map(static fn (string $item): string => Utils\Strings::lower($item), $this->denyFeatures);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'main_device_id' => $this->getMainDeviceId(),
			'state' => $this->getState()?->toArray(),
			'deny_features' => $this->getDenyFeatures(),
		];
	}

}
