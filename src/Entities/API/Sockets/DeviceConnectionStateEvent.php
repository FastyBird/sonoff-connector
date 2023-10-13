<?php declare(strict_types = 1);

/**
 * DeviceConnectionStateEvent.php
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
 * Device reported state entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceConnectionStateEvent implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('apikey')]
		private readonly string $apiKey,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('deviceid')]
		private readonly string $deviceId,
		#[ObjectMapper\Rules\MappedObjectValue(class: DeviceConnectionStateEventParams::class)]
		private readonly DeviceConnectionStateEventParams $params,
	)
	{
	}

	public function getApiKey(): string
	{
		return $this->apiKey;
	}

	public function getDeviceId(): string
	{
		return $this->deviceId;
	}

	public function getParams(): DeviceConnectionStateEventParams
	{
		return $this->params;
	}

	public function isOnline(): bool
	{
		return $this->getParams()->isOnline();
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'api_key' => $this->getApiKey(),
			'device_id' => $this->getDeviceId(),
			'params' => $this->getParams()->toArray(),
		];
	}

}
