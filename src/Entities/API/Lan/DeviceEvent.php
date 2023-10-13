<?php declare(strict_types = 1);

/**
 * DeviceEvent.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           18.05.23
 */

namespace FastyBird\Connector\Sonoff\Entities\API\Lan;

use FastyBird\Connector\Sonoff\Entities\API\Entity;
use Orisai\ObjectMapper;

/**
 * Device LAN event entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceEvent implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('ip_address')]
		private readonly string $ipAddress,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $domain,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private readonly int $port,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $type,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $seq,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly string|null $iv,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $encrypt,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: DeviceEventData::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly DeviceEventData|null $data,
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getIpAddress(): string
	{
		return $this->ipAddress;
	}

	public function getDomain(): string
	{
		return $this->domain;
	}

	public function getPort(): int
	{
		return $this->port;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getSeq(): string
	{
		return $this->seq;
	}

	public function getIv(): string|null
	{
		return $this->iv;
	}

	public function isEncrypted(): bool
	{
		return $this->encrypt;
	}

	public function getData(): DeviceEventData|null
	{
		return $this->data;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'ip_address' => $this->getIpAddress(),
			'domain' => $this->getDomain(),
			'port' => $this->getPort(),
			'type' => $this->getType(),
			'seq' => $this->getSeq(),
			'iv' => $this->getIv(),
			'encrypt' => $this->isEncrypted(),
			'data' => $this->getData()?->toArray(),
		];
	}

}
