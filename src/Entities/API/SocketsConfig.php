<?php declare(strict_types = 1);

/**
 * SocketsConfig.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           08.05.23
 */

namespace FastyBird\Connector\Sonoff\Entities\API;

use Nette;

/**
 * Sockets handshake configuration in entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SocketsConfig implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly int $hb = 0,
		private readonly int $hbInterval = 90,
	)
	{
	}

	public function hasHeartbeat(): bool
	{
		return $this->hb === 1;
	}

	public function getHeartbeatInterval(): int
	{
		return $this->hbInterval;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'heartbeat' => $this->hasHeartbeat(),
			'heartbeat_interval' => $this->getHeartbeatInterval(),
		];
	}

}
