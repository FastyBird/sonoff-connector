<?php declare(strict_types = 1);

/**
 * ConnectorPropertyIdentifier.php
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
 * Connector property identifiers
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ConnectorPropertyIdentifier: string
{

	case CLIENT_MODE = 'mode';

	case USERNAME = 'username';

	case PASSWORD = 'password';

	case REGION = 'region';

	case APP_ID = 'app_id';

	case APP_SECRET = 'app_secret';

	case GATEWAY_ID = 'gateway_id';

	case GATEWAY_API_KEY = 'gateway_api_key';

}
