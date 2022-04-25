<?php declare(strict_types = 1);

/**
 * ISonoffConnector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           25.04.22
 */

namespace FastyBird\SonoffConnector\Entities;

use FastyBird\DevicesModule\Entities as DevicesModuleEntities;

/**
 * Sonoff connector entity interface
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ISonoffConnector extends DevicesModuleEntities\Connectors\IConnector
{

}
