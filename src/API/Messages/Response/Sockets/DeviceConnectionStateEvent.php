<?php declare(strict_types = 1);

/**
 * DeviceConnectionStateEvent.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           08.05.23
 */

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Sockets;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;

/**
 * Device reported state entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class DeviceConnectionStateEvent implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('apikey')]
		private string $apiKey,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('deviceid')]
		private string $deviceId,
		#[ObjectMapper\Rules\MappedObjectValue(class: DeviceConnectionStateEventParams::class)]
		private DeviceConnectionStateEventParams $params,
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
