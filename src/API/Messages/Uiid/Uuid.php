<?php declare(strict_types = 1);

/**
 * Uuid.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           18.09.23
 */

namespace FastyBird\Connector\Sonoff\API\Messages\Uiid;

use FastyBird\Connector\Sonoff\API;

/**
 * UIID base interface
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Uuid extends API\Messages\Message
{

	public const VALUE_ON = 'on';

	public const VALUE_OFF = 'off';

	public const VALUE_STAY = 'stay';

	public const VALUE_START = 'start';

	public const VALUE_STOP = 'stop';

	public const VALUE_GET = 'get';

	public const TEMPERATURE_COLD = 'cold';

	public const TEMPERATURE_MIDDLE = 'middle';

	public const TEMPERATURE_WARM = 'warm';

	public const SPEED_SLOW = 'slow';

	public const SPEED_MODERATE = 'moderate';

	public const SPEED_FAST = 'fast';

	public const MODE_NORMAL = 'normal';

	public const MODE_NATURAL = 'natural';

	public const MODE_SLEEP = 'sleep';

	public const COMMAND_CAPTURE = 'capture';

	public const COMMAND_CAPTURE_CANCEL = 'captureCancel';

	public const COMMAND_EDIT = 'edit';

	public const COMMAND_TRANSMIT = 'transmit';

	public const COMMAND_TRIGGER = 'trigger';

	/**
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function toStates(): array;

}
