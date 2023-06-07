<?php declare(strict_types = 1);

/**
 * SocketsHandshake.php
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
 * Sockets handshake in entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SocketsHandshake implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $apikey,
		private readonly SocketsConfig|null $config = null,
	)
	{
	}

	public function getApiKey(): string
	{
		return $this->apikey;
	}

	public function getConfig(): SocketsConfig
	{
		return $this->config ?? new SocketsConfig();
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'api_key' => $this->getApiKey(),
			'config' => $this->getConfig()->toArray(),
		];
	}

}
