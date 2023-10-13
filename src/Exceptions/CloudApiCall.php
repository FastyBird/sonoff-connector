<?php declare(strict_types = 1);

/**
 * CloudApiCall.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           06.05.23
 */

namespace FastyBird\Connector\Sonoff\Exceptions;

use Psr\Http\Message;
use RuntimeException;
use Throwable;

class CloudApiCall extends RuntimeException implements Exception
{

	public function __construct(
		string $message,
		private readonly Message\RequestInterface|null $request = null,
		private readonly Message\ResponseInterface|null $response = null,
		int $code = 0,
		Throwable|null $previous = null,
	)
	{
		parent::__construct($message, $code, $previous);
	}

	public function getRequest(): Message\RequestInterface|null
	{
		try {
			$this->request?->getBody()->rewind();
		} catch (RuntimeException) {
			// Just ignore it
		}

		return $this->request;
	}

	public function getResponse(): Message\ResponseInterface|null
	{
		try {
			$this->response?->getBody()->rewind();
		} catch (RuntimeException) {
			// Just ignore it
		}

		return $this->response;
	}

}
