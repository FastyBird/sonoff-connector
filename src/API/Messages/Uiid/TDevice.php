<?php declare(strict_types = 1);

/**
 * TDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           28.09.23
 */

namespace FastyBird\Connector\Sonoff\API\Messages\Uiid;

use FastyBird\Connector\Sonoff\Types;

/**
 * Device state mapper
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
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
			Types\ParameterType::DEVICE->value => [
				[
					Types\PropertyParameter::NAME->value => Types\Parameter::STATUS_LED->value,
					Types\PropertyParameter::VALUE->value => $this->getStatusLed(),
				],
				[
					Types\PropertyParameter::NAME->value => Types\Parameter::FIRMWARE_VERSION->value,
					Types\PropertyParameter::VALUE->value => $this->getFirmwareVersion(),
				],
				[
					Types\PropertyParameter::NAME->value => Types\Parameter::SSID->value,
					Types\PropertyParameter::VALUE->value => $this->getSsid(),
				],
				[
					Types\PropertyParameter::NAME->value => Types\Parameter::RSSI->value,
					Types\PropertyParameter::VALUE->value => $this->getRssi(),
				],
			],
		];
	}

}
