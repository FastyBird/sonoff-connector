<?php declare(strict_types = 1);

/**
 * Device.php
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

use Nette;
use Ramsey\Uuid;

/**
 * Base device message entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Device implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Uuid\UuidInterface $connector,
		private readonly string $identifier,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
		];
	}

}