<?php declare(strict_types = 1);

/**
 * DeviceConfiguration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           07.05.23
 */

namespace FastyBird\Connector\Sonoff\Entities\API;

use Nette;

/**
 * Device configuration entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceConfiguration implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string|null $p2pServerName = null,
		private readonly string|null $p2pAccout = null,
		private readonly string|null $p2pLicense = null,
	)
	{
	}

	public function getP2pServerName(): string|null
	{
		return $this->p2pServerName;
	}

	public function getP2pAccount(): string|null
	{
		return $this->p2pAccout;
	}

	public function getP2pLicense(): string|null
	{
		return $this->p2pLicense;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'p2p_server_name' => $this->getP2pServerName(),
			'p2p_account' => $this->getP2pAccount(),
			'p2p_license' => $this->getP2pLicense(),
		];
	}

}
