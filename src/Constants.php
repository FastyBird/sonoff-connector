<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           06.05.23
 */

namespace FastyBird\Connector\Sonoff;

/**
 * Connector constants
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	public const RESOURCES_FOLDER = __DIR__ . '/../resources';

	public const COOLKIT_APP_ID = '4s1FXKC9FaGfoqXhmXSJneb3qcm1gOak'; // CoolKit application ID extracted from ha CoolKit addon

	public const COOLKIT_APP_SECRET = 'oKvCM06gvwkRbfetd6qWRrbC3rFrbIpV'; // CoolKit application secret extracted from ha CoolKit addon

	public const DEFAULT_APP_ID = 'R8Oq3y0eSZSYdKccHlrQzT1ACCOUT9Gv'; // CoolKit application ID extracted from ha CoolKit addon

	public const DEFAULT_APP_SECRET = '1ve5Qk9GXfUhKAn1svnKwpAlxXkMarru'; // CoolKit application secret extracted from ha CoolKit addon

	public const VALUE_NOT_AVAILABLE = 'N/A';

	public const CHANNEL_GROUP = '/^(?P<group>[a-zA-Z]+)(_(?P<outlet>[0-9]+))?$/';

	public const STATE_READING_DELAY = 5_000.0;

	public const HEARTBEAT_DELAY = 2_500.0;

	public const WRITE_DEBOUNCE_DELAY = 2_000.0;

}
