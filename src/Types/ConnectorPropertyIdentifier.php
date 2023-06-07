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

use Consistence;
use function strval;

/**
 * Connector property identifiers
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectorPropertyIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const IDENTIFIER_CLIENT_MODE = 'mode';

	public const IDENTIFIER_USERNAME = 'username';

	public const IDENTIFIER_PASSWORD = 'password';

	public const IDENTIFIER_REGION = 'region';

	public const IDENTIFIER_APP_ID = 'app_id';

	public const IDENTIFIER_APP_SECRET = 'app_secret';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
