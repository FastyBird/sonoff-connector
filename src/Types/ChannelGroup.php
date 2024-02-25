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

/**
 * Device parameters channels groups
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ChannelGroup: string
{

	case SWITCHES = 'switches';

	case CONFIGURE = 'configure';

	case PULSES = 'pulses';

	case RF_LIST = 'rfList';

}
