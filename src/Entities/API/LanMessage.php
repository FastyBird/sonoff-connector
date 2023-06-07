<?php declare(strict_types = 1);

/**
 * LanMessage.php
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

namespace FastyBird\Connector\Sonoff\Entities\API;

use Nette;

/**
 * Device LAN message entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class LanMessage implements Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<string> $data
	 */
	public function __construct(
		private readonly string $id,
		private readonly string $ipAddress,
		private readonly string $domain,
		private readonly int $port,
		private readonly string $type,
		private readonly string $seq,
		private readonly string|null $iv = null,
		private readonly bool $encrypt = false,
		private readonly array $data = [],
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

	/**
	 * @return array<string>
	 */
	public function getData(): array
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
			'data' => $this->getData(),
		];
	}

}
