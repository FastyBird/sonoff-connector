<?php declare(strict_types = 1);

/**
 * ParameterStatus.php
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
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;

/**
 * Device or channel parameter status entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class ParameterStatus implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $identifier,
		private readonly float|int|string|bool|MetadataTypes\SwitchPayload|DateTimeInterface|null $value,
	)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getValue(): float|int|string|bool|MetadataTypes\SwitchPayload|DateTimeInterface|null
	{
		return $this->value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'value' => DevicesUtilities\ValueHelper::flattenValue($this->getValue()),
		];
	}

}
