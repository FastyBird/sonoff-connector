<?php declare(strict_types = 1);

/**
 * ClientMode.php
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
 * Connector client modes
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ClientMode extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const MODE_LAN = 'lan';

	public const MODE_CLOUD = 'cloud';

	public const MODE_AUTO = 'auto';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
