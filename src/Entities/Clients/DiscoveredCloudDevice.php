<?php declare(strict_types = 1);

/**
 * DiscoveredCloudDevice.php
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

namespace FastyBird\Connector\Sonoff\Entities\Clients;

use Orisai\ObjectMapper;
use function array_map;

/**
 * Discovered cloud device entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DiscoveredCloudDevice implements Entity
{

	/**
	 * @param array<DiscoveredDeviceParameter> $parameters
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $apiKey,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $deviceKey,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private readonly int $uiid,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $name,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $description,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $brandName,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $brandLogo,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $productModel,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $model,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $mac,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(class: DiscoveredDeviceParameter::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private readonly array $parameters,
	)
	{
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

	public function getBrandLogo(): string|null
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

	/**
	 * @return array<DiscoveredDeviceParameter>
	 */
	public function getParameters(): array
	{
		return $this->parameters;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
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
			'parameters' => array_map(
				static fn (DiscoveredDeviceParameter $parameter): array => $parameter->toArray(),
				$this->getParameters(),
			),
		];
	}

}
