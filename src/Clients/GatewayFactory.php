<?php declare(strict_types = 1);

/**
 * GatewayFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           13.10.23
 */

namespace FastyBird\Connector\Sonoff\Clients;

use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Types;

/**
 * Gateway client factory
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface GatewayFactory extends ClientFactory
{

	public const MODE = Types\ClientMode::GATEWAY;

	public function create(Entities\SonoffConnector $connector): Gateway;

}
