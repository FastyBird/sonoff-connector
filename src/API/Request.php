<?php declare(strict_types = 1);

/**
 * Request.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           14.09.23
 */

namespace FastyBird\Connector\Sonoff\API;

use FastyBird\Connector\Sonoff\Exceptions;
use InvalidArgumentException;
use RingCentral\Psr7;
use RuntimeException;

/**
 * HTTP request
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Request extends Psr7\Request
{

	/**
	 * @param array<string, string|array<string>>|null $headers
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(
		string $method,
		string $uri,
		array|null $headers = null,
		string|null $body = null,
	)
	{
		try {
			parent::__construct($method, $uri, $headers ?? [], $body);
		} catch (InvalidArgumentException $ex) {
			throw new Exceptions\InvalidArgument('Request could not be created', $ex->getCode(), $ex);
		}
	}

	public function getContent(): string|null
	{
		try {
			$content = $this->getBody()->getContents();

			$this->getBody()->rewind();

			return $content;
		} catch (RuntimeException) {
			return null;
		}
	}

}
