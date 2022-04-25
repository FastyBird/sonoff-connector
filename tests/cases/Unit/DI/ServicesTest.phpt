<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\SonoffConnector\Hydrators;
use FastyBird\SonoffConnector\Schemas;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../BaseTestCase.php';

/**
 * @testCase
 */
final class ServicesTest extends BaseTestCase
{

	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		Assert::notNull($container->getByType(Hydrators\SonoffConnectorHydrator::class));
		Assert::notNull($container->getByType(Hydrators\SonoffDeviceHydrator::class));

		Assert::notNull($container->getByType(Schemas\SonoffConnectorSchema::class));
		Assert::notNull($container->getByType(Schemas\SonoffDeviceSchema::class));
	}

}

$test_case = new ServicesTest();
$test_case->run();
