<?php declare(strict_types = 1);

/**
 * WebSocketClientFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           12.10.23
 */

namespace FastyBird\Connector\Sonoff\Services;

use InvalidArgumentException;
use Ratchet;
use Ratchet\Client;
use React\EventLoop;
use React\Promise;
use React\Socket;

/**
 * OpenPulsar websockets client factory
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WebSocketClientFactory
{

	public function __construct(
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<Ratchet\Client\WebSocket>
	 */
	public function create(string $topicUrl): Promise\PromiseInterface
	{
		try {
			$reactConnector = new Socket\Connector([
				'dns' => '8.8.8.8',
				'timeout' => 10,
				'tls' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
					'check_hostname' => false,
				],
			]);

			$connector = new Client\Connector($this->eventLoop, $reactConnector);

			return $connector($topicUrl);
		} catch (InvalidArgumentException $ex) {
			return Promise\reject($ex);
		}
	}

}
