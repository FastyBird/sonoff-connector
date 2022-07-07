<?php declare(strict_types = 1);

/**
 * SonoffDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           25.04.22
 */

namespace FastyBird\SonoffConnector\Entities;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\Metadata\Types as MetadataTypes;

/**
 * @ORM\Entity
 */
class SonoffDevice extends DevicesModuleEntities\Devices\Device implements ISonoffDevice
{

	public const DEVICE_TYPE = 'sonoff';

	/**
	 * {@inheritDoc}
	 */
	public function getType(): string
	{
		return self::DEVICE_TYPE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDiscriminatorName(): string
	{
		return self::DEVICE_TYPE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getSource(): MetadataTypes\ModuleSourceType|MetadataTypes\PluginSourceType|MetadataTypes\ConnectorSourceType
	{
		return MetadataTypes\ConnectorSourceType::get(MetadataTypes\ConnectorSourceType::SOURCE_CONNECTOR_SONOFF);
	}

}
