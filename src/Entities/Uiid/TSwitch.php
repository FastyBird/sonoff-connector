<?php declare(strict_types = 1);

/**
 * TSwitch.php
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
 * Single switch states mapper
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @method string|null getSwitch()
 * @method string|null getStartup()
 * @method string|null getPulse()
 * @method int|null getPulseWidth()
 */
trait TSwitch
{

	/**
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function toStates(): array
	{
		return [
			Types\ParameterType::CHANNEL => [
				[
					Types\PropertyParameter::NAME => Types\Parameter::SWITCH,
					Types\PropertyParameter::VALUE => $this->getSwitch(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH,
				],
				[
					Types\PropertyParameter::NAME => Types\Parameter::STARTUP,
					Types\PropertyParameter::VALUE => $this->getStartup(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH,
				],
				[
					Types\PropertyParameter::NAME => Types\Parameter::PULSE,
					Types\PropertyParameter::VALUE => $this->getPulse(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH,
				],
				[
					Types\PropertyParameter::NAME => Types\Parameter::PULSE_WIDTH,
					Types\PropertyParameter::VALUE => $this->getPulseWidth(),
					Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH,
				],
			],
		];
	}

}
