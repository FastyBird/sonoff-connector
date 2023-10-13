<?php declare(strict_types = 1);

/**
 * TDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           28.09.23
 */

namespace FastyBird\Connector\Sonoff\Entities\Uiid;

use FastyBird\Connector\Sonoff\Types;

/**
 * Device state mapper
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @method string|null getStatusLed()
 * @method string|null getFirmwareVersion()
 * @method string|null getSsid()
 * @method int|null getRssi()
 */
trait TDevice
{

	/**
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function toStates(): array
	{
		return [
			Types\ParameterType::DEVICE => [
				[
					Types\PropertyParameter::NAME => Types\Parameter::STATUS_LED,
					Types\PropertyParameter::VALUE => $this->getStatusLed(),
				],
				[
					Types\PropertyParameter::NAME => Types\Parameter::FIRMWARE_VERSION,
					Types\PropertyParameter::VALUE => $this->getFirmwareVersion(),
				],
				[
					Types\PropertyParameter::NAME => Types\Parameter::SSID,
					Types\PropertyParameter::VALUE => $this->getSsid(),
				],
				[
					Types\PropertyParameter::NAME => Types\Parameter::RSSI,
					Types\PropertyParameter::VALUE => $this->getRssi(),
				],
			],
		];
	}

}
