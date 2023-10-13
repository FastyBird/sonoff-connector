<?php declare(strict_types = 1);

/**
 * ApplicationHandshake.php
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

namespace FastyBird\Connector\Sonoff\Entities\API\Sockets;

use FastyBird\Connector\Sonoff\Entities;
use Orisai\ObjectMapper;

/**
 * Application sockets handshake in entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ApplicationHandshake implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('apikey')]
		private readonly string $apiKey,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: ApplicationConfig::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly ApplicationConfig|null $config = null,
	)
	{
	}

	public function getApiKey(): string
	{
		return $this->apiKey;
	}

	public function getConfig(): ApplicationConfig
	{
		return $this->config ?? new ApplicationConfig();
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
