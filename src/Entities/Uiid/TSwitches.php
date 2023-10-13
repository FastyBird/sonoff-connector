<?php declare(strict_types = 1);

/**
 * TSwitches.php
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
use function array_map;
use function array_merge;

/**
 * Multiple switches states mapper
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @method array<SwitchState> getSwitches()
 * @method array<SwitchConfiguration> getConfiguration()
 * @method array<SwitchPulse> getPulses()
 */
trait TSwitches
{

	/**
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function toStates(): array
	{
		return [
			Types\ParameterType::CHANNEL => [
				array_merge(
					array_map(
						static fn (SwitchState $state): array => [
							Types\PropertyParameter::NAME => Types\Parameter::SWITCH,
							Types\PropertyParameter::VALUE => $state->getSwitch(),
							Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH . '_' . $state->getOutlet(),
						],
						$this->getSwitches(),
					),
					array_map(
						static fn (SwitchConfiguration $state): array => [
							Types\PropertyParameter::NAME => Types\Parameter::STARTUP,
							Types\PropertyParameter::VALUE => $state->getStartup(),
							Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH . '_' . $state->getOutlet(),
						],
						$this->getConfiguration(),
					),
					array_map(
						static fn (SwitchPulse $state): array => [
							Types\PropertyParameter::NAME => Types\Parameter::PULSE,
							Types\PropertyParameter::VALUE => $state->getPulse(),
							Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH . '_' . $state->getOutlet(),
						],
						$this->getPulses(),
					),
					array_map(
						static fn (SwitchPulse $state): array => [
							Types\PropertyParameter::NAME => Types\Parameter::PULSE_WIDTH,
							Types\PropertyParameter::VALUE => $state->getWidth(),
							Types\PropertyParameter::GROUP => Types\ParameterGroup::SWITCH . '_' . $state->getOutlet(),
						],
						$this->getPulses(),
					),
				),
			],
		];
	}

}
