<?php declare(strict_types = 1);

/**
 * WsMessage.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Properties
 * @since          1.0.0
 *
 * @date           07.05.23
 */

namespace FastyBird\Connector\Sonoff\Entities\API;

use FastyBird\Connector\Sonoff\Entities\Clients\Entity;
use Nette;
use React\EventLoop;
use React\Promise;
use stdClass;

/**
 * Websocket message entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Properties
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WsMessage implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly stdClass $payload,
		private readonly string $action,
		private readonly Promise\Deferred|null $deferred = null,
		private readonly EventLoop\TimerInterface|null $timer = null,
	)
	{
	}

	public function getPayload(): stdClass
	{
		return $this->payload;
	}

	public function getAction(): string
	{
		return $this->action;
	}

	public function getDeferred(): Promise\Deferred|null
	{
		return $this->deferred;
	}

	public function getTimer(): EventLoop\TimerInterface|null
	{
		return $this->timer;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'payload' => $this->getPayload(),
		];
	}

}
