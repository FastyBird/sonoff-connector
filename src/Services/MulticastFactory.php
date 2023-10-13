<?php declare(strict_types = 1);

/**
 * MulticastFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Services
 * @since          1.0.0
 *
 * @date           12.10.23
 */

namespace FastyBird\Connector\Sonoff\Services;

use BadMethodCallException;
use Clue\React\Multicast;
use Nette;
use React\Datagram;
use React\EventLoop;
use RuntimeException;

/**
 * React multicast server factory
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Services
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class MulticastFactory
{

	use Nette\SmartObject;

	public function __construct(
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @throws BadMethodCallException
	 * @throws RuntimeException
	 */
	public function create(string $address, int $port): Datagram\SocketInterface
	{
		return (new Multicast\Factory($this->eventLoop))->createReceiver($address . ':' . $port);
	}

}
