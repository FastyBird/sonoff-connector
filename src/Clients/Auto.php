<?php declare(strict_types = 1);

/**
 * Auto.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           08.05.23
 */

namespace FastyBird\Connector\Sonoff\Clients;

use BadMethodCallException;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use InvalidArgumentException;
use Nette;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use Throwable;
use function in_array;

/**
 * Lan client
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Auto extends ClientProcess implements Client
{

	use Nette\SmartObject;

	private Lan $lanClient;

	private Cloud $cloudClient;

	public function __construct(
		Helpers\Device $deviceHelper,
		DevicesUtilities\DeviceConnection $deviceConnectionManager,
		DateTimeFactory\Factory $dateTimeFactory,
		EventLoop\LoopInterface $eventLoop,
		private readonly MetadataDocuments\DevicesModule\Connector $connector,
		private readonly LanFactory $lanClientFactory,
		private readonly CloudFactory $cloudClientFactory,
	)
	{
		parent::__construct(
			$deviceHelper,
			$deviceConnectionManager,
			$dateTimeFactory,
			$eventLoop,
		);
	}

	/**
	 * @throws BadMethodCallException
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\CloudApiCall
	 * @throws InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function connect(): void
	{
		$this->lanClient = $this->lanClientFactory->create($this->connector, true);
		$this->cloudClient = $this->cloudClientFactory->create($this->connector, true);

		$this->processedDevices = [];
		$this->processedDevicesCommands = [];

		$this->handlerTimer = null;

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			function (): void {
				$this->registerLoopHandler();
			},
		);

		$this->cloudClient->connect();
		$this->lanClient->connect();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function disconnect(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}

		$this->cloudClient->disconnect();
		$this->lanClient->disconnect();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function readInformation(MetadataDocuments\DevicesModule\Device $device): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if (
			$this->deviceHelper->getIpAddress($device) !== null
			&& !in_array($device->getId()->toString(), $this->ignoredDevices, true)
		) {
			$this->lanClient->readInformation($device)
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->catch(function (Throwable $ex) use ($deferred, $device): void {
					$this->cloudClient->readInformation($device)
						->then(static function () use ($deferred): void {
							$deferred->resolve(true);
						})
						->catch(static function (Throwable $ex) use ($deferred): void {
							$deferred->reject($ex);
						});
				});
		} else {
			$this->cloudClient->readInformation($device)
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});
		}

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function readState(MetadataDocuments\DevicesModule\Device $device): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->cloudClient->readState($device)
			->then(static function () use ($deferred): void {
				$deferred->resolve(true);
			})
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

}
