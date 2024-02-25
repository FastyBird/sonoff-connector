<?php declare(strict_types = 1);

/**
 * TSwitch.php
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
 * Single switch states mapper
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
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
			Types\ParameterType::CHANNEL->value => [
				[
					Types\PropertyParameter::NAME->value => Types\Parameter::SWITCH->value,
					Types\PropertyParameter::VALUE->value => $this->getSwitch(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
				],
				[
					Types\PropertyParameter::NAME->value => Types\Parameter::STARTUP->value,
					Types\PropertyParameter::VALUE->value => $this->getStartup(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
				],
				[
					Types\PropertyParameter::NAME->value => Types\Parameter::PULSE->value,
					Types\PropertyParameter::VALUE->value => $this->getPulse(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
				],
				[
					Types\PropertyParameter::NAME->value => Types\Parameter::PULSE_WIDTH->value,
					Types\PropertyParameter::VALUE->value => $this->getPulseWidth(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
				],
			],
		];
	}

}
