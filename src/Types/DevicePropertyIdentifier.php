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
	public const IP_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS;

	public const ADDRESS = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_ADDRESS;

	public const STATE = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATE;

	public const HARDWARE_MODEL = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MODEL;

	public const HARDWARE_MAC_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS;

	public const FIRMWARE_VERSION = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_VERSION;

	public const RSSI = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_RSSI;

	public const SSID = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_SSID;

	public const STATUS_LED = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATUS_LED;

	public const API_KEY = 'api_key';

	public const DEVICE_KEY = 'device_key';

	public const BRAND_NAME = 'brand_name';

	public const BRAND_LOGO = 'brand_logo';

	public const PRODUCT_MODEL = 'product_model';

	public const PORT = 'port';

	public const UIID = 'uiid';

	public const STATE_READING_DELAY = 'state_reading_delay';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
