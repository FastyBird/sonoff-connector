<?php declare(strict_types = 1);

/**
 * UserRefresh.php
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
 * User token refreshed in entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class UserRefresh implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $at,
		private readonly string $rt,
	)
	{
	}

	public function getAccessToken(): string
	{
		return $this->at;
	}

	public function getRefreshToken(): string
	{
		return $this->rt;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'access_token' => $this->getAccessToken(),
			'refresh_token' => $this->getRefreshToken(),
		];
	}

}
