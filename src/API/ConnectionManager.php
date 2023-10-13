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

use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;

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
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getCloudApiConnection(Entities\SonoffConnector $connector): CloudApi
	{
		if ($this->cloudApiConnection === null) {
			$this->cloudApiConnection = $this->cloudApiFactory->create(
				$connector->getUsername(),
				$connector->getPassword(),
				$connector->getAppId(),
				$connector->getAppSecret(),
				$connector->getRegion(),
			);
		}

		return $this->cloudApiConnection;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getCloudWsConnection(Entities\SonoffConnector $connector): CloudWs
	{
		if ($this->cloudApiConnection?->getAccessToken() === null) {
			throw new Exceptions\InvalidState('Cloud API connection have to be established first');
		}

		if ($this->cloudWsConnection === null) {
			$this->cloudWsConnection = $this->cloudWsFactory->create(
				$this->cloudApiConnection->getAccessToken(),
				$connector->getAppId(),
				$connector->getAppSecret(),
				$connector->getRegion(),
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
