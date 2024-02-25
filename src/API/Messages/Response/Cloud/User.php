<?php declare(strict_types = 1);

/**
 * User.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           25.05.23
 */

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Cloud;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;

/**
 * User into entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class User implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue()]
		private int $accountLevel,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $countryCode,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $email,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('apikey')]
		private string $apiKey,
		#[ObjectMapper\Rules\BoolValue()]
		private bool $accountConsult,
		#[ObjectMapper\Rules\BoolValue()]
		private bool $appForumEnterHide,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $appVersion,
		#[ObjectMapper\Rules\BoolValue()]
		private bool $denyRecharge,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $ipCountry,
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
		return $this->apiKey;
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
