<?php declare(strict_types = 1);

/**
 * ThirdPartyDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           16.09.23
 */

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Cloud;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;

/**
 * Third party device entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class ThirdPartyDevice implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $name,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('deviceid')]
		private string $deviceId,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('apikey')]
		private string $apiKey,
		#[ObjectMapper\Modifiers\FieldName('devicekey')]
		private string $deviceKey,
	)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDeviceId(): string
	{
		return $this->deviceId;
	}

	public function getApiKey(): string
	{
		return $this->apiKey;
	}

	public function getDeviceKey(): string
	{
		return $this->deviceKey;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'name' => $this->getName(),
			'device_id' => $this->getDeviceId(),
			'api_key' => $this->getApiKey(),
			'device_key' => $this->getDeviceKey(),
		];
	}

}
