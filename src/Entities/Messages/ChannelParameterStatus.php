<?php declare(strict_types = 1);

/**
 * ChannelParameterStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           31.05.23
 */

namespace FastyBird\Connector\Sonoff\Entities\Messages;

use DateTimeInterface;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use function array_merge;

/**
 * Channel parameter status entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelParameterStatus extends ParameterStatus
{

	public function __construct(
		string $identifier,
		private readonly string $channel,
		float|MetadataTypes\SwitchPayload|bool|int|string|DateTimeInterface|null $value,
	)
	{
		parent::__construct($identifier, $value);
	}

	public function getChannel(): string
	{
		return $this->channel;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'channel' => $this->getChannel(),
		]);
	}

}
