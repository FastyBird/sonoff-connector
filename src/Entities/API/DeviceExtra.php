<?php declare(strict_types = 1);

/**
 * DeviceExtra.php
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

/**
 * Device extra info entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceExtra implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $model,
		private readonly string $ui,
		private readonly int $uiid,
		private readonly string|null $description,
		private readonly string $manufacturer,
		private readonly string $mac,
		private readonly string $apmac,
		private readonly string $modelInfo,
		private readonly string $brandId,
		private readonly string|null $chipid = null,
	)
	{
	}

	public function getModel(): string
	{
		return $this->model;
	}

	public function getUi(): string
	{
		return $this->ui;
	}

	public function getUiid(): int
	{
		return $this->uiid;
	}

	public function getDescription(): string|null
	{
		return $this->description;
	}

	public function getManufacturer(): string
	{
		return $this->manufacturer;
	}

	public function getMac(): string
	{
		return $this->mac;
	}

	public function getApMac(): string
	{
		return $this->apmac;
	}

	public function getModelInfo(): string
	{
		return $this->modelInfo;
	}

	public function getBrandId(): string
	{
		return $this->brandId;
	}

	public function getChipId(): string|null
	{
		return $this->chipid;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'model' => $this->getModel(),
			'ui' => $this->getUi(),
			'uiid' => $this->getUiid(),
			'description' => $this->getDescription(),
			'manufacturer' => $this->getManufacturer(),
			'mac' => $this->getMac(),
			'ap_mac' => $this->getApMac(),
			'model_info' => $this->getModelInfo(),
			'brand_id' => $this->getBrandId(),
			'chip_id' => $this->getChipId(),
		];
	}

}
