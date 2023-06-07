<?php declare(strict_types = 1);

/**
 * CloudWsFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           06.05.23
 */

namespace FastyBird\Connector\Sonoff\API;

use FastyBird\Connector\Sonoff\Types;

/**
 * Cloud WS factory
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface CloudWsFactory
{

	public function create(
		string $identifier,
		string $accessToken,
		string $appId,
		string $apiKey,
		Types\Region $region,
	): CloudWs;

}
