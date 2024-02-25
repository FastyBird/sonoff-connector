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
		if ($identifier === Types\Parameter::STATUS_LED->value) {
			return Types\DevicePropertyIdentifier::STATUS_LED->value;
		}

		if ($identifier === Types\Parameter::FIRMWARE_VERSION->value) {
			return Types\DevicePropertyIdentifier::FIRMWARE_VERSION->value;
		}

		if ($identifier === Types\Parameter::RSSI->value) {
			return Types\DevicePropertyIdentifier::RSSI->value;
		}

		if ($identifier === Types\Parameter::SSID->value) {
			return Types\DevicePropertyIdentifier::SSID->value;
		}

		if ($identifier === Types\Parameter::BSSID->value) {
			return Types\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value;
		}

		return $identifier;
	}

	public static function devicePropertyToParameter(string $identifier): string
	{
		if ($identifier === Types\DevicePropertyIdentifier::STATUS_LED->value) {
			return Types\Parameter::STATUS_LED->value;
		}

		if ($identifier === Types\DevicePropertyIdentifier::FIRMWARE_VERSION->value) {
			return Types\Parameter::FIRMWARE_VERSION->value;
		}

		if ($identifier === Types\DevicePropertyIdentifier::RSSI->value) {
			return Types\Parameter::RSSI->value;
		}

		if ($identifier === Types\DevicePropertyIdentifier::SSID->value) {
			return Types\Parameter::SSID->value;
		}

		if ($identifier === Types\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value) {
			return Types\Parameter::BSSID->value;
		}

		return $identifier;
	}

}
