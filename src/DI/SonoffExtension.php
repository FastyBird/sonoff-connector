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

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Clients;
use FastyBird\Connector\Sonoff\Commands;
use FastyBird\Connector\Sonoff\Connector;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Hydrators;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Schemas;
use FastyBird\Connector\Sonoff\Services;
use FastyBird\Connector\Sonoff\Subscribers;
use FastyBird\Connector\Sonoff\Writers;
use FastyBird\Library\Application\Boot as ApplicationBoot;
use FastyBird\Library\Exchange\DI as ExchangeDI;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Devices\DI as DevicesDI;
use Nette\Bootstrap;
use Nette\DI;
use Nettrine\ORM as NettrineORM;
use function array_keys;
use function array_pop;
use const DIRECTORY_SEPARATOR;

/**
 * Sonoff connector
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class SonoffExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbSonoffConnector';

	public static function register(
		ApplicationBoot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Bootstrap\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$logger = $builder->addDefinition($this->prefix('logger'), new DI\Definitions\ServiceDefinition())
			->setType(Sonoff\Logger::class)
			->setAutowired(false);

		/**
		 * WRITERS
		 */

		$builder->addFactoryDefinition($this->prefix('writers.event'))
			->setImplement(Writers\EventFactory::class)
			->getResultDefinition()
			->setType(Writers\Event::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('writers.exchange'))
			->setImplement(Writers\ExchangeFactory::class)
			->getResultDefinition()
			->setType(Writers\Exchange::class)
			->setArguments([
				'logger' => $logger,
			])
			->addTag(ExchangeDI\ExchangeExtension::CONSUMER_STATE, false);

		/**
		 * SERVICES & FACTORIES
		 */

		$builder->addDefinition(
			$this->prefix('services.httpClientFactory'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Services\HttpClientFactory::class);

		$builder->addDefinition(
			$this->prefix('services.webSocketClientFactory'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Services\WebSocketClientFactory::class);

		$builder->addDefinition(
			$this->prefix('services.multicastFactory'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Services\MulticastFactory::class);

		/**
		 * CLIENTS
		 */

		$builder->addFactoryDefinition($this->prefix('clients.lan'))
			->setImplement(Clients\LanFactory::class)
			->getResultDefinition()
			->setType(Clients\Lan::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('clients.cloud'))
			->setImplement(Clients\CloudFactory::class)
			->getResultDefinition()
			->setType(Clients\Cloud::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('clients.discover'))
			->setImplement(Clients\DiscoveryFactory::class)
			->getResultDefinition()
			->setType(Clients\Discovery::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('clients.gateway'))
			->setImplement(Clients\GatewayFactory::class)
			->getResultDefinition()
			->setType(Clients\Gateway::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * API
		 */

		$builder->addDefinition(
			$this->prefix('api.connectionsManager'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(API\ConnectionManager::class);

		$builder->addFactoryDefinition($this->prefix('api.cloudApi'))
			->setImplement(API\CloudApiFactory::class)
			->getResultDefinition()
			->setType(API\CloudApi::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('api.cloudWs'))
			->setImplement(API\CloudWsFactory::class)
			->getResultDefinition()
			->setType(API\CloudWs::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('api.lan'))
			->setImplement(API\LanApiFactory::class)
			->getResultDefinition()
			->setType(API\LanApi::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * MESSAGES QUEUE
		 */

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.device'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreDevice::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.deviceConnectionState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreDeviceConnectionState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.parametersStates'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreParametersStates::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.write.writeDevicePropertyState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\WriteDevicePropertyState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.write.writeChannelPropertyState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\WriteChannelPropertyState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers::class)
			->setArguments([
				'consumers' => $builder->findByType(Queue\Consumer::class),
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.queue'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Queue::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * SUBSCRIBERS
		 */

		$builder->addDefinition($this->prefix('subscribers.properties'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Properties::class);

		$builder->addDefinition($this->prefix('subscribers.controls'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Controls::class);

		/**
		 * JSON-API SCHEMAS
		 */

		$builder->addDefinition($this->prefix('schemas.connector'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Connectors\Connector::class);

		$builder->addDefinition($this->prefix('schemas.device'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Devices\Device::class);

		$builder->addDefinition($this->prefix('schemas.channel'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\Channel::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition($this->prefix('hydrators.connector'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Connectors\Connector::class);

		$builder->addDefinition($this->prefix('hydrators.device'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Devices\Device::class);

		$builder->addDefinition($this->prefix('hydrators.channel'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Channels\Channel::class);

		/**
		 * HELPERS
		 */

		$builder->addDefinition($this->prefix('helpers.entity'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\MessageBuilder::class);

		$builder->addDefinition($this->prefix('helpers.connector'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Connector::class);

		$builder->addDefinition($this->prefix('helpers.device'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Device::class);

		/**
		 * COMMANDS
		 */

		$builder->addDefinition($this->prefix('commands.execute'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Execute::class);

		$builder->addDefinition($this->prefix('commands.discover'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Discover::class);

		$builder->addDefinition($this->prefix('commands.install'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Install::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * CONNECTOR
		 */

		$builder->addFactoryDefinition($this->prefix('connector'))
			->setImplement(Connector\ConnectorFactory::class)
			->addTag(
				DevicesDI\DevicesExtension::CONNECTOR_TYPE_TAG,
				Entities\Connectors\Connector::TYPE,
			)
			->getResultDefinition()
			->setType(Connector\Connector::class)
			->setArguments([
				'clientsFactories' => $builder->findByType(Clients\ClientFactory::class),
				'writersFactories' => $builder->findByType(Writers\WriterFactory::class),
				'logger' => $logger,
			]);
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * DOCTRINE ENTITIES
		 */

		$services = $builder->findByTag(NettrineORM\DI\OrmAttributesExtension::DRIVER_TAG);

		if ($services !== []) {
			$services = array_keys($services);
			$ormAttributeDriverServiceName = array_pop($services);

			$ormAttributeDriverService = $builder->getDefinition($ormAttributeDriverServiceName);

			if ($ormAttributeDriverService instanceof DI\Definitions\ServiceDefinition) {
				$ormAttributeDriverService->addSetup(
					'addPaths',
					[[__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities']],
				);

				$ormAttributeDriverChainService = $builder->getDefinitionByType(
					Persistence\Mapping\Driver\MappingDriverChain::class,
				);

				if ($ormAttributeDriverChainService instanceof DI\Definitions\ServiceDefinition) {
					$ormAttributeDriverChainService->addSetup('addDriver', [
						$ormAttributeDriverService,
						'FastyBird\Connector\Sonoff\Entities',
					]);
				}
			}
		}

		/**
		 * APPLICATION DOCUMENTS
		 */

		$services = $builder->findByTag(Metadata\DI\MetadataExtension::DRIVER_TAG);

		if ($services !== []) {
			$services = array_keys($services);
			$documentAttributeDriverServiceName = array_pop($services);

			$documentAttributeDriverService = $builder->getDefinition($documentAttributeDriverServiceName);

			if ($documentAttributeDriverService instanceof DI\Definitions\ServiceDefinition) {
				$documentAttributeDriverService->addSetup(
					'addPaths',
					[[__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Documents']],
				);

				$documentAttributeDriverChainService = $builder->getDefinitionByType(
					MetadataDocuments\Mapping\Driver\MappingDriverChain::class,
				);

				if ($documentAttributeDriverChainService instanceof DI\Definitions\ServiceDefinition) {
					$documentAttributeDriverChainService->addSetup('addDriver', [
						$documentAttributeDriverService,
						'FastyBird\Connector\Sonoff\Documents',
					]);
				}
			}
		}
	}

	/**
	 * @return array<string>
	 */
	public function getTranslationResources(): array
	{
		return [
			__DIR__ . '/../Translations/',
		];
	}

}
