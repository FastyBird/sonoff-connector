<?php declare(strict_types = 1);

/**
 * PropertyParameter.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           30.09.23
 */

namespace FastyBird\Connector\Sonoff\Types;

/**
 * Property parameters
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum PropertyParameter: string
{

	case NAME = 'name';

	case VALUE = 'value';

	case GROUP = 'group';

}
