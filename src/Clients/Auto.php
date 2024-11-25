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
use FastyBird\Connector\Sonoff\Documents;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\DateTimeFactory;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use InvalidArgumentException;
use Nette;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use Throwable;
use TypeError;
use ValueError;
use function in_array;
use function React\Async\async;

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
		DateTimeFactory\Clock $clock,
		EventLoop\LoopInterface $eventLoop,
		private readonly Documents\Connectors\Connector $connector,
		private readonly LanFactory $lanClientFactory,
		private readonly CloudFactory $cloudClientFactory,
	)
	{
		parent::__construct(
			$deviceHelper,
			$deviceConnectionManager,
			$clock,
			$eventLoop,
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Mapping
	 * @throws BadMethodCallException
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\CloudApiCall
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 * @throws TypeError
	 * @throws ValueError
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
			async(function (): void {
				$this->registerLoopHandler();
			}),
		);

		$this->cloudClient->connect();
		$this->lanClient->connect();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 * @throws Exceptions\Runtime
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function readInformation(
		Documents\Devices\Device $device,
	): Promise\PromiseInterface
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\CloudApiCall
	 * @throws Exceptions\CloudApiError
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function readState(Documents\Devices\Device $device): Promise\PromiseInterface
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
