<?php declare(strict_types = 1);

/**
 * DiscoveredDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           06.05.23
 */

namespace FastyBird\Connector\Sonoff\Entities\Messages;

use Nette;
use Ramsey\Uuid;
use function array_map;

/**
 * Discovered cloud device property entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DiscoveredDevice implements Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<DiscoveredDeviceProperty> $properties
	 */
	public function __construct(
		private readonly Uuid\UuidInterface $connector,
		private readonly string $id,
		private readonly string $apiKey,
		private readonly string $deviceKey,
		private readonly int $uiid,
		private readonly string $name,
		private readonly string|null $description,
		private readonly string $brandName,
		private readonly string $brandLogo,
		private readonly string $productModel,
		private readonly string $model,
		private readonly string $mac,
		private readonly string|null $ipAddress,
		private readonly string|null $domain,
		private readonly int|null $port,
		private readonly array $properties,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getApiKey(): string
	{
		return $this->apiKey;
	}

	public function getDeviceKey(): string
	{
		return $this->deviceKey;
	}

	public function getUiid(): int
	{
		return $this->uiid;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): string|null
	{
		return $this->description;
	}

	public function getBrandName(): string
	{
		return $this->brandName;
	}

	public function getBrandLogo(): string
	{
		return $this->brandLogo;
	}

	public function getProductModel(): string
	{
		return $this->productModel;
	}

	public function getModel(): string
	{
		return $this->model;
	}

	public function getMac(): string
	{
		return $this->mac;
	}

	public function getIpAddress(): string|null
	{
		return $this->ipAddress;
	}

	public function getDomain(): string|null
	{
		return $this->domain;
	}

	public function getPort(): int|null
	{
		return $this->port;
	}

	/**
	 * @return array<DiscoveredDeviceProperty>
	 */
	public function getProperties(): array
	{
		return $this->properties;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector()->toString(),
			'id' => $this->getId(),
			'api_key' => $this->getApiKey(),
			'device_key' => $this->getDeviceKey(),
			'uiid' => $this->getUiid(),
			'name' => $this->getName(),
			'description' => $this->getDescription(),
			'brand_name' => $this->getBrandName(),
			'brand_logo' => $this->getBrandLogo(),
			'product_model' => $this->getProductModel(),
			'model' => $this->getModel(),
			'mac' => $this->getMac(),
			'ip_address' => $this->getIpAddress(),
			'domain' => $this->getDomain(),
			'port' => $this->getPort(),
			'properties' => array_map(
				static fn (DiscoveredDeviceProperty $property): array => $property->toArray(),
				$this->getProperties(),
			),
		];
	}

}
