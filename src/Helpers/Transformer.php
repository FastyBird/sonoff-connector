<?php declare(strict_types = 1);

/**
 * Transformer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           18.05.23
 */

namespace FastyBird\Connector\Sonoff\Helpers;

use FastyBird\Connector\Sonoff\Types;
use Nette;
use function base64_decode;
use function base64_encode;
use function md5;
use function openssl_decrypt;
use function openssl_encrypt;
use function strval;
use const OPENSSL_RAW_DATA;

/**
 * Devices data transformers
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Transformer
{

	use Nette\SmartObject;

	public static function decryptMessage(string $message, string $key, string $iv = ''): string|false
	{
		return openssl_decrypt(
			strval(base64_decode($message, true)),
			'AES-128-CBC',
			md5($key, true),
			OPENSSL_RAW_DATA,
			strval(base64_decode($iv, true)),
		);
	}

	public static function encryptMessage(string $message, string $key, string $iv = ''): string|false
	{
		$encrypted = openssl_encrypt(
			$message,
			'AES-128-CBC',
			md5($key, true),
			OPENSSL_RAW_DATA,
			strval(base64_decode($iv, true)),
		);

		if ($encrypted === false) {
			return false;
		}

		return base64_encode($encrypted);
	}

	public static function deviceParameterToProperty(string $identifier): string
	{
		if ($identifier === Types\Parameter::STATUS_LED) {
			return Types\DevicePropertyIdentifier::STATUS_LED;
		}

		if ($identifier === Types\Parameter::FIRMWARE_VERSION) {
			return Types\DevicePropertyIdentifier::FIRMWARE_VERSION;
		}

		if ($identifier === Types\Parameter::RSSI) {
			return Types\DevicePropertyIdentifier::RSSI;
		}

		if ($identifier === Types\Parameter::SSID) {
			return Types\DevicePropertyIdentifier::SSID;
		}

		if ($identifier === Types\Parameter::BSSID) {
			return Types\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS;
		}

		return $identifier;
	}

	public static function devicePropertyToParameter(string $identifier): string
	{
		if ($identifier === Types\DevicePropertyIdentifier::STATUS_LED) {
			return Types\Parameter::STATUS_LED;
		}

		if ($identifier === Types\DevicePropertyIdentifier::FIRMWARE_VERSION) {
			return Types\Parameter::FIRMWARE_VERSION;
		}

		if ($identifier === Types\DevicePropertyIdentifier::RSSI) {
			return Types\Parameter::RSSI;
		}

		if ($identifier === Types\DevicePropertyIdentifier::SSID) {
			return Types\Parameter::SSID;
		}

		if ($identifier === Types\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS) {
			return Types\Parameter::BSSID;
		}

		return $identifier;
	}

}
