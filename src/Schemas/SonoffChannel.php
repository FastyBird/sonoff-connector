<?php declare(strict_types = 1);

/**
 * SonoffChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           07.01.24
 */

namespace FastyBird\Connector\Sonoff\Schemas;

use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Sonoff device channel entity schema
 *
 * @extends DevicesSchemas\Channels\Channel<Entities\SonoffChannel>
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SonoffChannel extends DevicesSchemas\Channels\Channel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF . '/channel/' . Entities\SonoffChannel::TYPE;

	public function getEntityClass(): string
	{
		return Entities\SonoffChannel::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
