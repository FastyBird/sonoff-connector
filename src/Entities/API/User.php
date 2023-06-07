<?php declare(strict_types = 1);

/**
 * User.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.05.23
 */

namespace FastyBird\Connector\Sonoff\Entities\API;

use Nette;

/**
 * User into entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class User implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly int $accountLevel,
		private readonly string $countryCode,
		private readonly string $email,
		private readonly string $apikey,
		private readonly bool $accountConsult,
		private readonly bool $appForumEnterHide,
		private readonly string $appVersion,
		private readonly bool $denyRecharge,
		private readonly string $ipCountry,
	)
	{
	}

	public function getAccountLevel(): int
	{
		return $this->accountLevel;
	}

	public function getCountryCode(): string
	{
		return $this->countryCode;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function getApiKey(): string
	{
		return $this->apikey;
	}

	public function hasAccountConsult(): bool
	{
		return $this->accountConsult;
	}

	public function hasAppForumEnterHide(): bool
	{
		return $this->appForumEnterHide;
	}

	public function getAppVersion(): string
	{
		return $this->appVersion;
	}

	public function hasDenyRecharge(): bool
	{
		return $this->denyRecharge;
	}

	public function getIpCountry(): string
	{
		return $this->ipCountry;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'account_level' => $this->getAccountLevel(),
			'country_code' => $this->getCountryCode(),
			'email' => $this->getEmail(),
			'api_key' => $this->getApiKey(),
			'account_consult' => $this->hasAccountConsult(),
			'app_forum_enter_hide' => $this->hasAppForumEnterHide(),
			'app_version' => $this->getAppVersion(),
			'deny_recharge' => $this->hasDenyRecharge(),
			'ip_country' => $this->getIpCountry(),
		];
	}

}
