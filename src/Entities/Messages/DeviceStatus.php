<?php declare(strict_types = 1);

/**
 * DeviceDataPointStatus.php
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

use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Device status message entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceStatus extends Device
{

	/**
	 * @param array<DeviceParameterStatus|ChannelParameterStatus> $parameters
	 */
	public function __construct(
		Uuid\UuidInterface $connector,
		string $identifier,
		private readonly array $parameters,
	)
	{
		parent::__construct($connector, $identifier);
	}

	/**
	 * @return array<DeviceParameterStatus|ChannelParameterStatus>
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
				static fn (ParameterStatus $parameter): array => $parameter->toArray(),
				$this->getParameters(),
			),
		]);
	}

}
