<?php declare(strict_types = 1);

/**
 * SocketsLogin.php
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

namespace FastyBird\Connector\Sonoff\Entities\API;

use Nette;

/**
 * Sockets logged in entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SocketsLogin implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $IP,
		private readonly int $port,
		private readonly string $domain,
	)
	{
	}

	public function getIpAddress(): string
	{
		return $this->IP;
	}

	public function getDomain(): string
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
