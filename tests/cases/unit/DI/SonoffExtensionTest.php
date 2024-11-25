<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Clients;
use FastyBird\Connector\Sonoff\Commands;
use FastyBird\Connector\Sonoff\Connector;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Hydrators;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Schemas;
use FastyBird\Connector\Sonoff\Services;
use FastyBird\Connector\Sonoff\Subscribers;
use FastyBird\Connector\Sonoff\Tests;
use FastyBird\Connector\Sonoff\Writers;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use Nette;

final class SonoffExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertCount(2, $container->findByType(Writers\WriterFactory::class));

		self::assertNotNull($container->getByType(Clients\LanFactory::class, false));
		self::assertNotNull($container->getByType(Clients\CloudFactory::class, false));
		self::assertNotNull($container->getByType(Clients\DiscoveryFactory::class, false));

		self::assertNotNull($container->getByType(Services\HttpClientFactory::class, false));
		self::assertNotNull($container->getByType(Services\MulticastFactory::class, false));
		self::assertNotNull($container->getByType(Services\WebSocketClientFactory::class, false));

		self::assertNotNull($container->getByType(API\ConnectionManager::class, false));
		self::assertNotNull($container->getByType(API\CloudApiFactory::class, false));
		self::assertNotNull($container->getByType(API\CloudWsFactory::class, false));
		self::assertNotNull($container->getByType(API\LanApiFactory::class, false));

		self::assertNotNull($container->getByType(Queue\Consumers\StoreDevice::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\StoreDeviceConnectionState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\StoreParametersStates::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\WriteDevicePropertyState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\WriteChannelPropertyState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers::class, false));
		self::assertNotNull($container->getByType(Queue\Queue::class, false));

		self::assertNotNull($container->getByType(Subscribers\Properties::class, false));
		self::assertNotNull($container->getByType(Subscribers\Controls::class, false));

		self::assertNotNull($container->getByType(Schemas\Connectors\Connector::class, false));
		self::assertNotNull($container->getByType(Schemas\Devices\Device::class, false));

		self::assertNotNull($container->getByType(Hydrators\Connectors\Connector::class, false));
		self::assertNotNull($container->getByType(Hydrators\Devices\Device::class, false));

		self::assertNotNull($container->getByType(Helpers\MessageBuilder::class, false));

		self::assertNotNull($container->getByType(Commands\Execute::class, false));
		self::assertNotNull($container->getByType(Commands\Discover::class, false));
		self::assertNotNull($container->getByType(Commands\Install::class, false));

		self::assertNotNull($container->getByType(Connector\ConnectorFactory::class, false));
	}

}
