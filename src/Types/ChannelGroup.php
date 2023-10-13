<?php declare(strict_types = 1);

/**
 * ChannelGroup.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           23.05.23
 */

namespace FastyBird\Connector\Sonoff\Types;

use Consistence;
use function strval;

/**
 * Device parameters channels groups
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ChannelGroup extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const SWITCHES = 'switches';

	public const CONFIGURE = 'configure';

	public const PULSES = 'pulses';

	public const RF_LIST = 'rfList';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
