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

namespace FastyBird\Connector\Sonoff\Entities\API\Cloud;

use FastyBird\Connector\Sonoff\Entities;
use Orisai\ObjectMapper;

/**
 * Device configuration entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceConfiguration implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $p2pServerName = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('p2pAccout')]
		private readonly string|null $p2pAccount = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
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
		return $this->p2pAccount;
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
