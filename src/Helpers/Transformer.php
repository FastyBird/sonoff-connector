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

use DateTimeInterface;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\ValueObjects as MetadataValueObjects;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use function array_filter;
use function array_values;
use function base64_decode;
use function base64_encode;
use function boolval;
use function count;
use function floatval;
use function intval;
use function is_bool;
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

	/**
	 * @throws MetadataExceptions\InvalidState
	 */
	public static function transformValueFromDevice(
		MetadataTypes\DataType $dataType,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataValueObjects\StringEnumFormat|MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\CombinedEnumFormat|MetadataValueObjects\EquationFormat|null $format,
		string|int|float|bool|null $value,
	): float|int|string|bool|MetadataTypes\SwitchPayload|DateTimeInterface|null
	{
		if ($value === null) {
			return null;
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_STRING)) {
			return strval($value);
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			return is_bool($value) ? $value : boolval($value);
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_FLOAT)) {
			$floatValue = floatval($value);

			if ($format instanceof MetadataValueObjects\NumberRangeFormat) {
				if ($format->getMin() !== null && $format->getMin() > $floatValue) {
					return null;
				}

				if ($format->getMax() !== null && $format->getMax() < $floatValue) {
					return null;
				}
			}

			return $floatValue;
		}

		if (
			$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UCHAR)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_CHAR)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_USHORT)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SHORT)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UINT)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_INT)
		) {
			$intValue = intval($value);

			if ($format instanceof MetadataValueObjects\NumberRangeFormat) {
				if ($format->getMin() !== null && $format->getMin() > $intValue) {
					return null;
				}

				if ($format->getMax() !== null && $format->getMax() < $intValue) {
					return null;
				}
			}

			return $intValue;
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)) {
			if ($format instanceof MetadataValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => Utils\Strings::lower(strval($value)) === $item,
				));

				if (count($filtered) === 1) {
					return MetadataTypes\SwitchPayload::isValidValue(strval($value))
						? MetadataTypes\SwitchPayload::get(
							strval($value),
						)
						: null;
				}

				return null;
			} elseif ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (array $item): bool => $item[1] !== null
						&& Utils\Strings::lower(strval($item[1]->getValue())) === Utils\Strings::lower(
							strval($value),
						),
				));

				if (
					count($filtered) === 1
					&& $filtered[0][0] instanceof MetadataValueObjects\CombinedEnumFormatItem
				) {
					return MetadataTypes\SwitchPayload::isValidValue(strval($filtered[0][0]->getValue()))
						? MetadataTypes\SwitchPayload::get(
							strval($filtered[0][0]->getValue()),
						)
						: null;
				}

				return null;
			}
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)) {
			if ($format instanceof MetadataValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => Utils\Strings::lower(strval($value)) === $item,
				));

				if (count($filtered) === 1) {
					return strval($value);
				}

				return null;
			} elseif ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (array $item): bool => $item[1] !== null
						&& Utils\Strings::lower(strval($item[1]->getValue())) === Utils\Strings::lower(
							strval($value),
						),
				));

				if (
					count($filtered) === 1
					&& $filtered[0][0] instanceof MetadataValueObjects\CombinedEnumFormatItem
				) {
					return strval($filtered[0][0]->getValue());
				}

				return null;
			}
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_DATETIME)) {
			$value = Utils\DateTime::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, strval($value));

			return $value === false ? null : $value;
		}

		return null;
	}

	/**
	 * @throws MetadataExceptions\InvalidState
	 */
	public static function transformValueToDevice(
		MetadataTypes\DataType $dataType,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataValueObjects\StringEnumFormat|MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\CombinedEnumFormat|MetadataValueObjects\EquationFormat|null $format,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): string|int|float|bool|null
	{
		if ($value === null) {
			return null;
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			if (is_bool($value)) {
				return $value;
			}

			return null;
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)) {
			if ($format instanceof MetadataValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => Utils\Strings::lower(
						strval(DevicesUtilities\ValueHelper::flattenValue($value)),
					) === $item,
				));

				if (count($filtered) === 1) {
					return strval(DevicesUtilities\ValueHelper::flattenValue($value));
				}

				return null;
			} elseif ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (array $item): bool => $item[0] !== null
						&& Utils\Strings::lower(strval($item[0]->getValue())) === Utils\Strings::lower(
							strval(DevicesUtilities\ValueHelper::flattenValue($value)),
						),
				));

				if (
					count($filtered) === 1
					&& $filtered[0][2] instanceof MetadataValueObjects\CombinedEnumFormatItem
				) {
					return strval($filtered[0][2]->getValue());
				}

				return null;
			}
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)) {
			if ($format instanceof MetadataValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => Utils\Strings::lower(
						strval(DevicesUtilities\ValueHelper::flattenValue($value)),
					) === $item,
				));

				if (count($filtered) === 1) {
					return strval(DevicesUtilities\ValueHelper::flattenValue($value));
				}

				return null;
			} elseif ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (array $item): bool => $item[0] !== null
						&& Utils\Strings::lower(strval($item[0]->getValue())) === Utils\Strings::lower(
							strval(DevicesUtilities\ValueHelper::flattenValue($value)),
						),
				));

				if (
					count($filtered) === 1
					&& $filtered[0][2] instanceof MetadataValueObjects\CombinedEnumFormatItem
				) {
					return strval($filtered[0][2]->getValue());
				}

				return null;
			}
		}

		return DevicesUtilities\ValueHelper::flattenValue($value);
	}

}
