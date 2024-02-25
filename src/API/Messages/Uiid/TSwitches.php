<?php declare(strict_types = 1);

/**
 * TSwitches.php
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
use function array_map;
use function array_merge;

/**
 * Multiple switches states mapper
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
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
			Types\ParameterType::CHANNEL->value => [
				array_merge(
					array_map(
						static fn (SwitchState $state): array => [
							Types\PropertyParameter::NAME->value => Types\Parameter::SWITCH->value,
							Types\PropertyParameter::VALUE->value => $state->getSwitch(),
							Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value . '_' . $state->getOutlet(),
						],
						$this->getSwitches(),
					),
					array_map(
						static fn (SwitchConfiguration $state): array => [
							Types\PropertyParameter::NAME->value => Types\Parameter::STARTUP->value,
							Types\PropertyParameter::VALUE->value => $state->getStartup(),
							Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value . '_' . $state->getOutlet(),
						],
						$this->getConfiguration(),
					),
					array_map(
						static fn (SwitchPulse $state): array => [
							Types\PropertyParameter::NAME->value => Types\Parameter::PULSE->value,
							Types\PropertyParameter::VALUE->value => $state->getPulse(),
							Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value . '_' . $state->getOutlet(),
						],
						$this->getPulses(),
					),
					array_map(
						static fn (SwitchPulse $state): array => [
							Types\PropertyParameter::NAME->value => Types\Parameter::PULSE_WIDTH->value,
							Types\PropertyParameter::VALUE->value => $state->getWidth(),
							Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value . '_' . $state->getOutlet(),
						],
						$this->getPulses(),
					),
				),
			],
		];
	}

}
