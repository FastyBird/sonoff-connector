<?php declare(strict_types = 1);

/**
 * Writer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           14.05.23
 */

namespace FastyBird\Connector\Sonoff\Writers;

use FastyBird\Connector\Sonoff\Clients;
use FastyBird\Connector\Sonoff\Entities;

/**
 * Properties writer interface
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Writer
{

	public function connect(
		Entities\SonoffConnector $connector,
		Clients\Client $client,
	): void;

	public function disconnect(
		Entities\SonoffConnector $connector,
		Clients\Client $client,
	): void;

}
