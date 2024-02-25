<?php declare(strict_types = 1);

/**
 * Region.php
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
 * Cloud region
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Region: string
{

	case CHINA = 'cn';

	case ASIA = 'as';

	case AMERICA = 'us';

	case EUROPE = 'eu';

}
