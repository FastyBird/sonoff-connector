<?php declare(strict_types = 1);

/**
 * ConnectionManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           30.09.23
 */

namespace FastyBird\Connector\Sonoff\API;

use FastyBird\Connector\Sonoff\Documents;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use TypeError;
use ValueError;

/**
 * API connections manager
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectionManager
{

	use Nette\SmartObject;

	private LanApi|null $lanConnection = null;

	private CloudApi|null $cloudApiConnection = null;

	private CloudWs|null $cloudWsConnection = null;

	public function __construct(
		private readonly LanApiFactory $lanApiFactory,
		private readonly CloudApiFactory $cloudApiFactory,
		private readonly CloudWsFactory $cloudWsFactory,
		private readonly Helpers\Connector $connectorHelper,
	)
	{
	}

	public function getLanConnection(): LanApi
	{
		if ($this->lanConnection === null) {
			$this->lanConnection = $this->lanApiFactory->create();
		}

		return $this->lanConnection;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getCloudApiConnection(Documents\Connectors\Connector $connector): CloudApi
	{
		if ($this->cloudApiConnection === null) {
			$this->cloudApiConnection = $this->cloudApiFactory->create(
				$this->connectorHelper->getUsername($connector),
				$this->connectorHelper->getPassword($connector),
				$this->connectorHelper->getAppId($connector),
				$this->connectorHelper->getAppSecret($connector),
				$this->connectorHelper->getRegion($connector),
			);
		}

		return $this->cloudApiConnection;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getCloudWsConnection(Documents\Connectors\Connector $connector): CloudWs
	{
		if ($this->cloudApiConnection?->getAccessToken() === null) {
			throw new Exceptions\InvalidState('Cloud API connection have to be established first');
		}

		if ($this->cloudWsConnection === null) {
			$this->cloudWsConnection = $this->cloudWsFactory->create(
				$this->cloudApiConnection->getAccessToken(),
				$this->connectorHelper->getAppId($connector),
				$this->connectorHelper->getAppSecret($connector),
				$this->connectorHelper->getRegion($connector),
			);
		}

		return $this->cloudWsConnection;
	}

	public function __destruct()
	{
		$this->lanConnection?->disconnect();
		$this->cloudApiConnection?->disconnect();
		$this->cloudWsConnection?->disconnect();
	}

}
