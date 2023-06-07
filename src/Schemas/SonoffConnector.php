<?php declare(strict_types = 1);

/**
 * SonoffConnector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           06.05.23
 */

namespace FastyBird\Connector\Sonoff\Schemas;

use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Sonoff connector entity schema
 *
 * @extends DevicesSchemas\Connectors\Connector<Entities\SonoffConnector>
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SonoffConnector extends DevicesSchemas\Connectors\Connector
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF . '/connector/' . Entities\SonoffConnector::CONNECTOR_TYPE;

	public function getEntityClass(): string
	{
		return Entities\SonoffConnector::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
