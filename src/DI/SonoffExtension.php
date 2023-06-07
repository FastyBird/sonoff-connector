<?php declare(strict_types = 1);

/**
 * SonoffExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           06.05.23
 */

namespace FastyBird\Connector\Sonoff\DI;

use Doctrine\Persistence;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Clients;
use FastyBird\Connector\Sonoff\Commands;
use FastyBird\Connector\Sonoff\Connector;
use FastyBird\Connector\Sonoff\Consumers;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Hydrators;
use FastyBird\Connector\Sonoff\Schemas;
use FastyBird\Connector\Sonoff\Subscribers;
use FastyBird\Connector\Sonoff\Writers;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\Library\Exchange\DI as ExchangeDI;
use FastyBird\Module\Devices\DI as DevicesDI;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;
use const DIRECTORY_SEPARATOR;

/**
 * Sonoff connector
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class SonoffExtension extends DI\CompilerExtension
{

	public const NAME = 'fbSonoffConnector';

	public static function register(
		BootstrapBoot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		// @phpstan-ignore-next-line
		$config->onCompile[] = static function (
			BootstrapBoot\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'writer' => Schema\Expect::anyOf(
				Writers\Event::NAME,
				Writers\Exchange::NAME,
				Writers\Periodic::NAME,
			)->default(
				Writers\Periodic::NAME,
			),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$writer = null;

		if ($configuration->writer === Writers\Event::NAME) {
			$writer = $builder->addDefinition($this->prefix('writers.event'), new DI\Definitions\ServiceDefinition())
				->setType(Writers\Event::class)
				->setAutowired(false);
		} elseif ($configuration->writer === Writers\Exchange::NAME) {
			$writer = $builder->addDefinition($this->prefix('writers.exchange'), new DI\Definitions\ServiceDefinition())
				->setType(Writers\Exchange::class)
				->setAutowired(false)
				->addTag(ExchangeDI\ExchangeExtension::CONSUMER_STATUS, false);
		} elseif ($configuration->writer === Writers\Periodic::NAME) {
			$writer = $builder->addDefinition($this->prefix('writers.periodic'), new DI\Definitions\ServiceDefinition())
				->setType(Writers\Periodic::class)
				->setAutowired(false);
		}

		$builder->addFactoryDefinition($this->prefix('clients.lan'))
			->setImplement(Clients\LanFactory::class)
			->getResultDefinition()
			->setType(Clients\Lan::class)
			->setArguments([
				'writer' => $writer,
			]);

		$builder->addFactoryDefinition($this->prefix('clients.cloud'))
			->setImplement(Clients\CloudFactory::class)
			->getResultDefinition()
			->setType(Clients\Cloud::class)
			->setArguments([
				'writer' => $writer,
			]);

		$builder->addFactoryDefinition($this->prefix('clients.auto'))
			->setImplement(Clients\AutoFactory::class)
			->getResultDefinition()
			->setType(Clients\Auto::class)
			->setArguments([
				'writer' => $writer,
			]);

		$builder->addFactoryDefinition($this->prefix('clients.discover'))
			->setImplement(Clients\DiscoveryFactory::class)
			->getResultDefinition()
			->setType(Clients\Discovery::class);

		$builder->addFactoryDefinition($this->prefix('api.lanApi'))
			->setImplement(API\LanApiFactory::class)
			->getResultDefinition()
			->setType(API\LanApi::class);

		$builder->addFactoryDefinition($this->prefix('api.cloudApi'))
			->setImplement(API\CloudApiFactory::class)
			->getResultDefinition()
			->setType(API\CloudApi::class);

		$builder->addFactoryDefinition($this->prefix('api.cloudWs'))
			->setImplement(API\CloudWsFactory::class)
			->getResultDefinition()
			->setType(API\CloudWs::class);

		$builder->addDefinition(
			$this->prefix('consumers.messages.device.status'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\Status::class);

		$builder->addDefinition(
			$this->prefix('consumers.messages.device.state'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\State::class);

		$builder->addDefinition(
			$this->prefix('consumers.messages.device.discovery'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\DevicesDiscovery::class);

		$builder->addDefinition($this->prefix('consumers.messages'), new DI\Definitions\ServiceDefinition())
			->setType(Consumers\Messages::class)
			->setArguments([
				'consumers' => $builder->findByType(Consumers\Consumer::class),
			]);

		$builder->addDefinition($this->prefix('subscribers.properties'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Properties::class);

		$builder->addDefinition($this->prefix('subscribers.controls'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Controls::class);

		$builder->addDefinition($this->prefix('schemas.connector.sonoff'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\SonoffConnector::class);

		$builder->addDefinition($this->prefix('schemas.device.sonoff'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\SonoffDevice::class);

		$builder->addDefinition($this->prefix('hydrators.connector.sonoff'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\SonoffConnector::class);

		$builder->addDefinition($this->prefix('hydrators.device.sonoff'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\SonoffDevice::class);

		$builder->addDefinition($this->prefix('helpers.property'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Property::class);

		$builder->addDefinition($this->prefix('helpers.name'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Name::class);

		$builder->addFactoryDefinition($this->prefix('executor.factory'))
			->setImplement(Connector\ConnectorFactory::class)
			->addTag(
				DevicesDI\DevicesExtension::CONNECTOR_TYPE_TAG,
				Entities\SonoffConnector::CONNECTOR_TYPE,
			)
			->getResultDefinition()
			->setType(Connector\Connector::class)
			->setArguments([
				'clientsFactories' => $builder->findByType(Clients\ClientFactory::class),
			]);

		$builder->addDefinition($this->prefix('commands.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Initialize::class);

		$builder->addDefinition($this->prefix('commands.discover'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Discover::class);

		$builder->addDefinition($this->prefix('commands.execute'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Execute::class);
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * Doctrine entities
		 */

		$ormAnnotationDriverService = $builder->getDefinition('nettrineOrmAnnotations.annotationDriver');

		if ($ormAnnotationDriverService instanceof DI\Definitions\ServiceDefinition) {
			$ormAnnotationDriverService->addSetup(
				'addPaths',
				[[__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities']],
			);
		}

		$ormAnnotationDriverChainService = $builder->getDefinitionByType(
			Persistence\Mapping\Driver\MappingDriverChain::class,
		);

		if ($ormAnnotationDriverChainService instanceof DI\Definitions\ServiceDefinition) {
			$ormAnnotationDriverChainService->addSetup('addDriver', [
				$ormAnnotationDriverService,
				'FastyBird\Connector\Sonoff\Entities',
			]);
		}
	}

}
