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

use Consistence;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use function strval;

/**
 * Device property identifier types
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicePropertyIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const IDENTIFIER_IP_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS;

	public const IDENTIFIER_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_ADDRESS;

	public const IDENTIFIER_STATE = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATE;

	public const IDENTIFIER_HARDWARE_MODEL = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MODEL;

	public const IDENTIFIER_HARDWARE_MAC_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS;

	public const IDENTIFIER_FIRMWARE_VERSION = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_VERSION;

	public const IDENTIFIER_RSSI = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_RSSI;

	public const IDENTIFIER_SSID = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_SSID;

	public const IDENTIFIER_STATUS_LED = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATUS_LED;

	public const IDENTIFIER_API_KEY = 'api_key';

	public const IDENTIFIER_DEVICE_KEY = 'device_key';

	public const IDENTIFIER_BRAND_NAME = 'brand_name';

	public const IDENTIFIER_BRAND_LOGO = 'brand_logo';

	public const IDENTIFIER_PRODUCT_MODEL = 'product_model';

	public const IDENTIFIER_PORT = 'port';

	public const IDENTIFIER_UIID = 'uiid';

	public const IDENTIFIER_STATUS_READING_DELAY = 'status_reading_delay';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
