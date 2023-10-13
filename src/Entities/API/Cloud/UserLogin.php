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

namespace FastyBird\Connector\Sonoff\Entities\API\Cloud;

use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;

/**
 * User logged in entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class UserLogin implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('at')]
		private readonly string $accessToken,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('rt')]
		private readonly string $refreshToken,
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\Region::class)]
		private readonly Types\Region $region,
		#[ObjectMapper\Rules\MappedObjectValue(User::class)]
		private readonly User $user,
	)
	{
	}

	public function getAccessToken(): string
	{
		return $this->accessToken;
	}

	public function getRefreshToken(): string
	{
		return $this->refreshToken;
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
