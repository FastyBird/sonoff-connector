<?php declare(strict_types = 1);

/**
 * CloudSocketsEndpoint.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           06.05.23
 */

namespace FastyBird\Connector\Sonoff\Types;

/**
 * CoolKit sockets endpoint types
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum CloudSocketsEndpoint: string
{

	case CHINA = 'https://cn-dispa.coolkit.cn';

	case AMERICA = 'https://us-dispa.coolkit.cc';

	case EUROPE = 'https://eu-dispa.coolkit.cc';

	case ASIA = 'https://as-dispa.coolkit.cc';

}
