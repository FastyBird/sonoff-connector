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

namespace FastyBird\Connector\Sonoff\Entities\API;

use Nette;
use Nette\Utils;

/**
 * User device entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $name,
		private readonly string $deviceid,
		private readonly string $apikey,
		private readonly DeviceExtra $extra,
		private readonly string $brandName,
		private readonly string $brandLogo,
		private readonly bool $showBrand,
		private readonly string $productModel,
		private readonly string $devicekey,
		private readonly bool $online,
		private readonly DeviceConfiguration|null $devConfig = null,
		private readonly DeviceSettings|null $settings = null,
		private readonly Utils\ArrayHash|null $params = null,
		private readonly Utils\ArrayHash|null $gsmInfoData = null,
	)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDeviceId(): string
	{
		return $this->deviceid;
	}

	public function getApiKey(): string
	{
		return $this->apikey;
	}

	public function getExtra(): DeviceExtra
	{
		return $this->extra;
	}

	public function getBrandName(): string
	{
		return $this->brandName;
	}

	public function getBrandLogo(): string
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
		return $this->devicekey;
	}

	public function isOnline(): bool
	{
		return $this->online;
	}

	public function getConfiguration(): DeviceConfiguration|null
	{
		return $this->devConfig;
	}

	public function getSettings(): DeviceSettings|null
	{
		return $this->settings;
	}

	public function getParams(): Utils\ArrayHash|null
	{
		return $this->params;
	}

	public function getGsmInfoData(): Utils\ArrayHash|null
	{
		return $this->gsmInfoData;
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
			'configuration' => $this->getConfiguration()?->toArray(),
			'settings' => $this->getSettings()?->toArray(),
			'params' => $this->getParams() !== null ? (array) $this->getParams() : [],
			'get_gsm_info_data' => $this->getGsmInfoData() !== null ? (array) $this->getGsmInfoData() : [],
		];
	}

}
