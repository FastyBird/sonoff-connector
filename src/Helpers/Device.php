<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           18.11.23
 */

namespace FastyBird\Connector\Sonoff\Helpers;

use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use function assert;
use function floatval;
use function intval;
use function is_numeric;
use function is_string;

/**
 * Device helper
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device
{

	public function __construct(
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getIpAddress(MetadataDocuments\DevicesModule\Device $device): string|null
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IP_ADDRESS);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\DeviceVariableProperty::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getAddress(MetadataDocuments\DevicesModule\Device $device): string|null
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::ADDRESS);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\DeviceVariableProperty::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getPort(MetadataDocuments\DevicesModule\Device $device): int
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::PORT);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\DeviceVariableProperty::class,
		);

		$value = $property?->getValue();

		if (!is_numeric($value)) {
			return API\LanApi::DEVICE_PORT;
		}

		return intval($value);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getApiKey(MetadataDocuments\DevicesModule\Device $device): string|null
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::API_KEY);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\DeviceVariableProperty::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getDeviceKey(MetadataDocuments\DevicesModule\Device $device): string|null
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::DEVICE_KEY);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\DeviceVariableProperty::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getModel(MetadataDocuments\DevicesModule\Device $device): string|null
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::HARDWARE_MODEL);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\DeviceVariableProperty::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getUiid(MetadataDocuments\DevicesModule\Device $device): int
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::UIID);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\DeviceVariableProperty::class,
		);

		if ($property?->getValue() === null) {
			throw new Exceptions\InvalidState('Device UIID protocol is not defined. Device have to be re-discovered');
		}

		$value = $property->getValue();
		assert(is_numeric($value));

		return intval($value);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getStateReadingDelay(MetadataDocuments\DevicesModule\Device $device): float
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::STATE_READING_DELAY);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\DeviceVariableProperty::class,
		);

		if ($property?->getValue() === null) {
			return Entities\SonoffDevice::STATE_READING_DELAY;
		}

		$value = $property->getValue();
		assert(is_numeric($value));

		return floatval($value);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getHeartbeatDelay(MetadataDocuments\DevicesModule\Device $device): float
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::HEARTBEAT_DELAY);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\DeviceVariableProperty::class,
		);

		if ($property?->getValue() === null) {
			return Entities\SonoffDevice::HEARTBEAT_DELAY;
		}

		$value = $property->getValue();
		assert(is_numeric($value));

		return floatval($value);
	}

}
