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
 * @date           22.11.23
 */

namespace FastyBird\Connector\Sonoff\Helpers;

use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use function assert;
use function is_string;

/**
 * Connector helper
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector
{

	/**
	 * @param DevicesModels\Configuration\Connectors\Properties\Repository<MetadataDocuments\DevicesModule\ConnectorVariableProperty> $connectorsPropertiesConfigurationRepository
	 */
	public function __construct(
		private readonly DevicesModels\Configuration\Connectors\Properties\Repository $connectorsPropertiesConfigurationRepository,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function getClientMode(MetadataDocuments\DevicesModule\Connector $connector): Types\ClientMode
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::CLIENT_MODE);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\ConnectorVariableProperty::class,
		);

		$value = $property?->getValue();

		if (is_string($value) && Types\ClientMode::isValidValue($value)) {
			return Types\ClientMode::get($value);
		}

		throw new Exceptions\InvalidState('Connector mode is not configured');
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function getUsername(MetadataDocuments\DevicesModule\Connector $connector): string
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::USERNAME);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\ConnectorVariableProperty::class,
		);

		if ($property === null) {
			return '';
		}

		$value = $property->getValue();
		assert(is_string($value) || $value === null);

		return $value ?? '';
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function getPassword(MetadataDocuments\DevicesModule\Connector $connector): string
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::PASSWORD);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\ConnectorVariableProperty::class,
		);

		if ($property === null) {
			return '';
		}

		$value = $property->getValue();
		assert(is_string($value) || $value === null);

		return $value ?? '';
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function getAppId(MetadataDocuments\DevicesModule\Connector $connector): string
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::APP_ID);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\ConnectorVariableProperty::class,
		);

		if ($property === null) {
			return Sonoff\Constants::DEFAULT_APP_ID;
		}

		$value = $property->getValue();
		assert(is_string($value) || $value === null);

		return $value ?? Sonoff\Constants::DEFAULT_APP_ID;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function getAppSecret(MetadataDocuments\DevicesModule\Connector $connector): string
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::APP_SECRET);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\ConnectorVariableProperty::class,
		);

		if ($property === null) {
			return Sonoff\Constants::DEFAULT_APP_SECRET;
		}

		$value = $property->getValue();
		assert(is_string($value) || $value === null);

		return $value ?? Sonoff\Constants::DEFAULT_APP_SECRET;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function getRegion(MetadataDocuments\DevicesModule\Connector $connector): Types\Region
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::APP_SECRET);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\ConnectorVariableProperty::class,
		);

		$value = $property?->getValue();

		if (is_string($value) && Types\Region::isValidValue($value)) {
			return Types\Region::get($value);
		}

		return Types\Region::get(Types\Region::EUROPE);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function getGatewayId(MetadataDocuments\DevicesModule\Connector $connector): string|null
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::GATEWAY_ID);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\ConnectorVariableProperty::class,
		);

		if ($property === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value) || $value === null);

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function getGatewayApiKey(MetadataDocuments\DevicesModule\Connector $connector): string|null
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::GATEWAY_API_KEY);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\ConnectorVariableProperty::class,
		);

		if ($property === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value) || $value === null);

		return $value;
	}

}
