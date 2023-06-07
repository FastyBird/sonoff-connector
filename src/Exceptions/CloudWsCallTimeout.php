<?php declare(strict_types = 1);

/**
 * CloudWsCallTimeout.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           07.05.23
 */

namespace FastyBird\Connector\Sonoff\Exceptions;

use RuntimeException;

class CloudWsCallTimeout extends RuntimeException implements Exception
{

}
