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

use Consistence;
use function strval;

/**
 * Cloud region
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Region extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const REGION_CHINA = 'cn';

	public const REGION_ASIA = 'as';

	public const REGION_AMERICA = 'us';

	public const REGION_EUROPE = 'eu';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
