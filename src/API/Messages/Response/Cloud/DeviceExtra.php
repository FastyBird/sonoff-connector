<?php declare(strict_types = 1);

/**
 * DeviceExtra.php
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

/**
 * Device extra info entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class DeviceExtra implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $model,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $ui,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private int $uiid,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $description,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $manufacturer,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $mac,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $apmac,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $modelInfo,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $brandId,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('chipid')]
		private string|null $chipId = null,
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
