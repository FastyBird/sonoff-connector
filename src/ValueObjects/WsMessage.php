<?php declare(strict_types = 1);

/**
 * WsMessage.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     ValueObjects
 * @since          1.0.0
 *
 * @date           07.05.23
 */

namespace FastyBird\Connector\Sonoff\ValueObjects;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;
use React\EventLoop;
use React\Promise;
use stdClass;

/**
 * Websocket message
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     ValueObjects
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class WsMessage implements ObjectMapper\MappedObject
{

	/**
	 * @param Promise\Deferred<API\Messages\Message>|null $deferred
	 */
	public function __construct(
		#[ObjectMapper\Rules\InstanceOfValue(type: stdClass::class)]
		private stdClass $payload,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $action,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\InstanceOfValue(type: Promise\Deferred::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private Promise\Deferred|null $deferred = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\InstanceOfValue(type: EventLoop\TimerInterface::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private EventLoop\TimerInterface|null $timer = null,
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

	/**
	 * @return Promise\Deferred<API\Messages\Message>|null
	 */
	public function getDeferred(): Promise\Deferred|null
	{
		return $this->deferred;
	}

	public function getTimer(): EventLoop\TimerInterface|null
	{
		return $this->timer;
	}

}
