<?php declare(strict_types = 1);

/**
 * Gateway.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           13.10.23
 */

namespace FastyBird\Connector\Sonoff\Clients;

use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;
use function sprintf;

/**
 * Gateway client
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Gateway implements Client
{

	use Nette\SmartObject;

	public function __construct(
		private readonly MetadataDocuments\DevicesModule\Connector $connector,
		private readonly Sonoff\Logger $logger,
	)
	{
	}

	/**
	 * @throws Exceptions\NotImplemented
	 */
	public function connect(): void
	{
		$this->logger->error(
			'Trying to connect with gateway client',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
				'type' => 'gateway-client',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		throw new Exceptions\NotImplemented(
			sprintf('Gateway client is not implemented: %s', $this->connector->getId()->toString()),
		);
	}

	/**
	 * @throws Exceptions\NotImplemented
	 */
	public function disconnect(): void
	{
		$this->logger->error(
			'Trying to disconnect with gateway client',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
				'type' => 'gateway-client',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		throw new Exceptions\NotImplemented(
			sprintf('Gateway client is not implemented: %s', $this->connector->getId()->toString()),
		);
	}

}
