<?php declare(strict_types = 1);

/**
 * DeviceState.php
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

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Cloud;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;

/**
 * Device queried status entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class DeviceState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $deviceId,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ObjectValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private API\Messages\Uiid\Uuid|null $state = null,
	)
	{
	}

	public function getDeviceId(): string
	{
		return $this->deviceId;
	}

	public function getState(): API\Messages\Uiid\Uuid|null
	{
		return $this->state;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'device_id' => $this->getDeviceId(),
			'state' => $this->getState()?->toArray(),
		];
	}

}
