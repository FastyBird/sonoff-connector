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
	public const CHINA = 'https://cn-dispa.coolkit.cn';

	public const AMERICA = 'https://us-dispa.coolkit.cc';

	public const EUROPE = 'https://eu-dispa.coolkit.cc';

	public const ASIA = 'https://as-dispa.coolkit.cc';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
