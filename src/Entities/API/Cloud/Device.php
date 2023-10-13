<?php declare(strict_types = 1);

/**
 * Device.php
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
 * User device entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device implements Entities\API\Entity
{

	/**
	 * @param array<string> $denyFeatures
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $name,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('deviceid')]
		private readonly string $deviceId,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('apikey')]
		private readonly string $apiKey,
		#[ObjectMapper\Rules\MappedObjectValue(DeviceExtra::class)]
		private readonly DeviceExtra $extra,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $brandName,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $brandLogo,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $showBrand,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $productModel,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('devicekey')]
		private readonly string $deviceKey,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $online,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: DeviceConfiguration::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('devConfig')]
		private readonly DeviceConfiguration|null $configuration = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: DeviceSettings::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly DeviceSettings|null $settings = null,
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

	public function getName(): string
	{
		return $this->name;
	}

	public function getDeviceId(): string
	{
		return $this->deviceId;
	}

	public function getApiKey(): string
	{
		return $this->apiKey;
	}

	public function getExtra(): DeviceExtra
	{
		return $this->extra;
	}

	public function getBrandName(): string
	{
		return $this->brandName;
	}

	public function getBrandLogo(): string|null
	{
		return $this->brandLogo;
	}

	public function isShowBrand(): bool
	{
		return $this->showBrand;
	}

	public function getProductModel(): string
	{
		return $this->productModel;
	}

	public function getDeviceKey(): string
	{
		return $this->deviceKey;
	}

	public function isOnline(): bool
	{
		return $this->online;
	}

	public function getConfiguration(): DeviceConfiguration|null
	{
		return $this->configuration;
	}

	public function getSettings(): DeviceSettings|null
	{
		return $this->settings;
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
			'name' => $this->getName(),
			'device_id' => $this->getDeviceId(),
			'api_key' => $this->getApiKey(),
			'extra' => $this->getExtra()->toArray(),
			'brand_name' => $this->getBrandName(),
			'brand_logo' => $this->getBrandLogo(),
			'show_brand' => $this->isShowBrand(),
			'product_model' => $this->getProductModel(),
			'device_key' => $this->getDeviceKey(),
			'configuration' => $this->getConfiguration()?->toArray(),
			'settings' => $this->getSettings()?->toArray(),
			'state' => $this->getState()?->toArray(),
			'deny_features' => $this->getDenyFeatures(),
		];
	}

}
