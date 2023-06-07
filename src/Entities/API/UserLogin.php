<?php declare(strict_types = 1);

/**
 * UserLogin.php
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

use FastyBird\Connector\Sonoff\Types;
use Nette;

/**
 * User logged in entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class UserLogin implements Entity
{

	use Nette\SmartObject;

	private Types\Region $region;

	public function __construct(
		private readonly string $at,
		private readonly string $rt,
		string $region,
		private readonly User $user,
	)
	{
		$this->region = Types\Region::get($region);
	}

	public function getAccessToken(): string
	{
		return $this->at;
	}

	public function getRefreshToken(): string
	{
		return $this->rt;
	}

	public function getRegion(): Types\Region
	{
		return $this->region;
	}

	public function getUser(): User
	{
		return $this->user;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'access_token' => $this->getAccessToken(),
			'refresh_token' => $this->getRefreshToken(),
			'region' => $this->getRegion()->getValue(),
			'user' => $this->getUser()->toArray(),
		];
	}

}
