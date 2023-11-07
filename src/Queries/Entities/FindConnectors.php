<?php declare(strict_types = 1);

/**
 * FindConnectors.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           06.10.23
 */

namespace FastyBird\Connector\Sonoff\Queries\Entities;

use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find connectors entities query
 *
 * @template T of Entities\SonoffConnector
 * @extends  DevicesQueries\Entities\FindConnectors<T>
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindConnectors extends DevicesQueries\Entities\FindConnectors
{

}
