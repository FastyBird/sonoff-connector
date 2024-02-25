<?php declare(strict_types = 1);

/**
 * DevicePropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           14.05.23
 */

namespace FastyBird\Connector\Sonoff\Types;

use FastyBird\Module\Devices\Types as DevicesTypes;

/**
 * Device property identifier types
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum DevicePropertyIdentifier: string
{

	case IP_ADDRESS = DevicesTypes\DevicePropertyIdentifier::IP_ADDRESS->value;

	case ADDRESS = DevicesTypes\DevicePropertyIdentifier::ADDRESS->value;

	case STATE = DevicesTypes\DevicePropertyIdentifier::STATE->value;

	case HARDWARE_MODEL = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MODEL->value;

	case HARDWARE_MAC_ADDRESS = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value;

	case FIRMWARE_VERSION = DevicesTypes\DevicePropertyIdentifier::FIRMWARE_VERSION->value;

	case RSSI = DevicesTypes\DevicePropertyIdentifier::RSSI->value;

	case SSID = DevicesTypes\DevicePropertyIdentifier::SSID->value;

	case STATUS_LED = DevicesTypes\DevicePropertyIdentifier::STATUS_LED->value;

	case API_KEY = 'api_key';

	case DEVICE_KEY = 'device_key';

	case BRAND_NAME = 'brand_name';

	case BRAND_LOGO = 'brand_logo';

	case PRODUCT_MODEL = 'product_model';

	case PORT = 'port';

	case UIID = 'uiid';

	case STATE_READING_DELAY = 'state_reading_delay';

	case HEARTBEAT_DELAY = 'heartbeat_delay';

}
