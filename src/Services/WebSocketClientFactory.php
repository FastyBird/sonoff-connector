<?php declare(strict_types = 1);

/**
 * WebSocketClientFactory.php
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

use Ratchet;
use Ratchet\Client;
use React\EventLoop;
use React\Promise;
use React\Socket;
use Throwable;

/**
 * Sonoff websockets client factory
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Services
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class WebSocketClientFactory
{

	public function __construct(private EventLoop\LoopInterface $eventLoop)
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
					'allow_self_signed' => true,
				],
			]);

			$connector = new Client\Connector($this->eventLoop, $reactConnector);

			return $connector($topicUrl);
		} catch (Throwable $ex) {
			return Promise\reject($ex);
		}
	}

}
