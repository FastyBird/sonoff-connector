<?php declare(strict_types = 1);

/**
 * Device.php
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
use Nette\Utils;
use Orisai\ObjectMapper;
use function array_map;

/**
 * User device entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class Device implements API\Messages\Message
{

	/**
	 * @param array<string> $denyFeatures
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $name,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('deviceid')]
		private string $deviceId,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('apikey')]
		private string $apiKey,
		#[ObjectMapper\Rules\MappedObjectValue(DeviceExtra::class)]
		private DeviceExtra $extra,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $brandName,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $brandLogo,
		#[ObjectMapper\Rules\BoolValue()]
		private bool $showBrand,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $productModel,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('devicekey')]
		private string $deviceKey,
		#[ObjectMapper\Rules\BoolValue()]
		private bool $online,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: DeviceConfiguration::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('devConfig')]
		private DeviceConfiguration|null $configuration = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: DeviceSettings::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private DeviceSettings|null $settings = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ObjectValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private API\Messages\Uiid\Uuid|null $state = null,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\StringValue(notEmpty: true),
		)]
		private array $denyFeatures = [],
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

	public function getState(): API\Messages\Uiid\Uuid|null
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
