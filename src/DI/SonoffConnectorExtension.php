<?php declare(strict_types = 1);

/**
 * SonoffConnectorExtension.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           25.04.22
 */

namespace FastyBird\SonoffConnector\DI;

use Doctrine\Persistence;
use FastyBird\SonoffConnector\Hydrators;
use FastyBird\SonoffConnector\Schemas;
use Nette;
use Nette\DI;

/**
 * Sonoff connector
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class SonoffConnectorExtension extends DI\CompilerExtension
{

	/**
	 * @param Nette\Configurator $config
	 * @param string $extensionName
	 *
	 * @return void
	 */
	public static function register(
		Nette\Configurator $config,
		string $extensionName = 'fbSonoffConnector'
	): void {
		$config->onCompile[] = function (
			Nette\Configurator $config,
			DI\Compiler $compiler
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new SonoffConnectorExtension());
		};
	}

	/**
	 * {@inheritDoc}
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		// API schemas
		$builder->addDefinition($this->prefix('schemas.connector.sonoff'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\SonoffConnectorSchema::class);

		$builder->addDefinition($this->prefix('schemas.device.sonoff'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\SonoffDeviceSchema::class);

		// API hydrators
		$builder->addDefinition($this->prefix('hydrators.connector.sonoff'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\SonoffConnectorHydrator::class);

		$builder->addDefinition($this->prefix('hydrators.device.sonoff'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\SonoffDeviceHydrator::class);
	}

	/**
	 * {@inheritDoc}
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
			$ormAnnotationDriverService->addSetup('addPaths', [[__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities']]);
		}

		$ormAnnotationDriverChainService = $builder->getDefinitionByType(Persistence\Mapping\Driver\MappingDriverChain::class);

		if ($ormAnnotationDriverChainService instanceof DI\Definitions\ServiceDefinition) {
			$ormAnnotationDriverChainService->addSetup('addDriver', [
				$ormAnnotationDriverService,
				'FastyBird\SonoffConnector\Entities',
			]);
		}
	}

}
