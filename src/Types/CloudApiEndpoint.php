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

use Consistence;
use function strval;

/**
 * CoolKit api endpoint types
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class CloudApiEndpoint extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const ENDPOINT_CHINA = 'https://cn-apia.coolkit.cn';

	public const ENDPOINT_AMERICA = 'https://us-apia.coolkit.cc';

	public const ENDPOINT_EUROPE = 'https://eu-apia.coolkit.cc';

	public const ENDPOINT_ASIA = 'https://as-apia.coolkit.cc';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
