<?php declare(strict_types = 1);

/**
 * CloudApiEndpoint.php
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
 * CoolKit api endpoint types
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum CloudApiEndpoint: string
{

	case CHINA = 'https://cn-apia.coolkit.cn';

	case AMERICA = 'https://us-apia.coolkit.cc';

	case EUROPE = 'https://eu-apia.coolkit.cc';

	case ASIA = 'https://as-apia.coolkit.cc';

}
