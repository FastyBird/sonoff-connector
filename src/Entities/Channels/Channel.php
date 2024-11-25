<?php declare(strict_types = 1);

/**
 * Channel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           07.01.23
 */

namespace FastyBird\Connector\Sonoff\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Core\Application\Entities\Mapping as ApplicationMapping;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Ramsey\Uuid;
use function assert;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class Channel extends DevicesEntities\Channels\Channel
{

	public const TYPE = 'sonoff-connector';

	public function __construct(
		Entities\Devices\Device $device,
		string $identifier,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $identifier, $name, $id);
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\Sources\Connector
	{
		return MetadataTypes\Sources\Connector::SONOFF;
	}

	public function getDevice(): Entities\Devices\Device
	{
		assert($this->device instanceof Entities\Devices\Device);

		return $this->device;
	}

}
