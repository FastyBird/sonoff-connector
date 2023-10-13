<?php declare(strict_types = 1);

/**
 * ApplicationConfig.php
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

namespace FastyBird\Connector\Sonoff\Entities\API\Sockets;

use FastyBird\Connector\Sonoff\Entities\API\Entity;
use Orisai\ObjectMapper;

/**
 * Application sockets handshake configuration in entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ApplicationConfig implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName('hb')]
		private readonly int $heartbeat = 0,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName('hbInterval')]
		private readonly int $heartbeatInterval = 90,
	)
	{
	}

	public function hasHeartbeat(): bool
	{
		return $this->heartbeat === 1;
	}

	public function getHeartbeatInterval(): int
	{
		return $this->heartbeatInterval;
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
