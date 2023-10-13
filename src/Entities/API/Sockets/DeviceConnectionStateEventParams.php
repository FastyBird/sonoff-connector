<?php declare(strict_types = 1);

/**
 * DeviceConnectionStateEventParams.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           18.09.23
 */

namespace FastyBird\Connector\Sonoff\Entities\API\Sockets;

use FastyBird\Connector\Sonoff\Entities;
use Orisai\ObjectMapper;

/**
 * Device reported state params entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceConnectionStateEventParams implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private readonly bool $online,
	)
	{
	}

	public function isOnline(): bool
	{
		return $this->online;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'is_online' => $this->isOnline(),
		];
	}

}
