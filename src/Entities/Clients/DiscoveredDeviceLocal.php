<?php declare(strict_types = 1);

/**
 * DiscoveredDeviceProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           15.05.23
 */

namespace FastyBird\Connector\Sonoff\Entities\Clients;

use Nette;

/**
 * Discovered cloud device entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DiscoveredDeviceLocal implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $ipAddress,
		private readonly string|null $domain,
		private readonly int $port,
	)
	{
	}

	public function getIpAddress(): string
	{
		return $this->ipAddress;
	}

	public function getDomain(): string|null
	{
		return $this->domain;
	}

	public function getPort(): int
	{
		return $this->port;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'ip_address' => $this->getIpAddress(),
			'domain' => $this->getDomain(),
			'port' => $this->getPort(),
		];
	}

}
