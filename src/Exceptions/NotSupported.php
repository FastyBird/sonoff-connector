<?php declare(strict_types = 1);

/**
 * NotSupported.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           05.06.23
 */

namespace FastyBird\Connector\Sonoff\Exceptions;

use InvalidArgumentException as PHPInvalidArgumentException;

class NotSupported extends PHPInvalidArgumentException implements Exception
{

}
