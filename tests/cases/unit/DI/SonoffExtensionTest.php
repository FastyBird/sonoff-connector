<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\Sonoff\Hydrators;
use FastyBird\Connector\Sonoff\Schemas;
use FastyBird\Connector\Sonoff\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use Nette;

final class SonoffExtensionTest extends BaseTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertNotNull($container->getByType(Schemas\SonoffDevice::class, false));
		self::assertNotNull($container->getByType(Schemas\SonoffConnector::class, false));

		self::assertNotNull($container->getByType(Hydrators\SonoffDevice::class, false));
		self::assertNotNull($container->getByType(Hydrators\SonoffConnector::class, false));
	}

}
