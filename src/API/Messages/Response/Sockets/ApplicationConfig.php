<?php declare(strict_types = 1);

/**
 * ApplicationConfig.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           08.05.23
 */

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Sockets;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;

/**
 * Application sockets handshake configuration in entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class ApplicationConfig implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName('hb')]
		private int $heartbeat = 0,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName('hbInterval')]
		private int $heartbeatInterval = 90,
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
