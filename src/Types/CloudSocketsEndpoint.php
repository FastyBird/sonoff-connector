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

use Consistence;
use function strval;

/**
 * CoolKit sockets endpoint types
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class CloudSocketsEndpoint extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const ENDPOINT_CHINA = 'https://cn-dispa.coolkit.cn';

	public const ENDPOINT_AMERICA = 'https://us-dispa.coolkit.cc';

	public const ENDPOINT_EUROPE = 'https://eu-dispa.coolkit.cc';

	public const ENDPOINT_ASIA = 'https://as-dispa.coolkit.cc';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
