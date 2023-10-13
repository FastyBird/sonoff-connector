<?php declare(strict_types = 1);

/**
 * StoreParametersStates.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           24.05.23
 */

namespace FastyBird\Connector\Sonoff\Entities\Messages;

use FastyBird\Connector\Sonoff\Entities;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Device state message entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreParametersStates extends Device
{

	/**
	 * @param array<Entities\Messages\States\DeviceParameterState|Entities\Messages\States\ChannelParameterState> $parameters
	 */
	public function __construct(
		Uuid\UuidInterface $connector,
		string $identifier,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\States\DeviceParameterState::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\States\ChannelParameterState::class),
			]),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private readonly array $parameters,
	)
	{
		parent::__construct($connector, $identifier);
	}

	/**
	 * @return array<Entities\Messages\States\DeviceParameterState|Entities\Messages\States\ChannelParameterState>
	 */
	public function getParameters(): array
	{
		return $this->parameters;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'parameters' => array_map(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (Entities\Messages\States\DeviceParameterState|Entities\Messages\States\ChannelParameterState $parameter): array => $parameter->toArray(),
				$this->getParameters(),
			),
		]);
	}

}
