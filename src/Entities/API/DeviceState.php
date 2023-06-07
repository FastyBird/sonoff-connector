<?php declare(strict_types = 1);

/**
 * DeviceState.php
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

namespace FastyBird\Connector\Sonoff\Entities\API;

use Nette;
use Nette\Utils;
use function boolval;

/**
 * Device reported state entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceState implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $apikey,
		private readonly string $deviceid,
		private readonly Utils\ArrayHash $params,
	)
	{
	}

	public function getApiKey(): string
	{
		return $this->apikey;
	}

	public function getDeviceId(): string
	{
		return $this->deviceid;
	}

	public function getParams(): Utils\ArrayHash
	{
		return $this->params;
	}

	public function isOnline(): bool
	{
		if ($this->params->offsetExists('online')) {
			return boolval($this->params->offsetGet('online'));
		}

		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'api_key' => $this->getApiKey(),
			'device_id' => $this->getDeviceId(),
			'is_online' => $this->isOnline(),
		];
	}

}
