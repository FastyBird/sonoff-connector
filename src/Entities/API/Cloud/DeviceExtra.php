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

namespace FastyBird\Connector\Sonoff\Entities\API\Cloud;

use FastyBird\Connector\Sonoff\Entities;
use Orisai\ObjectMapper;

/**
 * Device extra info entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceExtra implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $model,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $ui,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private readonly int $uiid,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $description,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $manufacturer,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $mac,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $apmac,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $modelInfo,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $brandId,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('chipid')]
		private readonly string|null $chipId = null,
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
		return $this->chipId;
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
