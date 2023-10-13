<?php declare(strict_types = 1);

/**
 * Entity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           14.09.23
 */

namespace FastyBird\Connector\Sonoff\Helpers;

use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use function array_key_exists;
use function assert;
use function class_exists;
use function sprintf;
use const DIRECTORY_SEPARATOR;

/**
 * Entity helper
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Entity
{

	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private const KNOW_UUID_TYPES = [1, 2, 3, 4, 5, 6, 7, 8, 9, 14, 15, 16, 17, 18, 19, 22, 24, 25, 27, 28, 29, 30, 31, 32, 33, 34, 36, 44, 52, 57, 59, 77, 78, 81, 82, 83, 84, 102, 103, 104, 107, 195, 1_770, 1_771];

	public function __construct(
		private readonly ObjectMapper\Processing\Processor $entityMapper,
		private readonly MetadataSchemas\Validator $schemaValidator,
	)
	{
	}

	/**
	 * @template T of Entities\API\Entity|Entities\Messages\Entity|Entities\Clients\Entity|Entities\Commands\Entity|Entities\Uiid\Entity
	 *
	 * @param class-string<T> $entity
	 * @param array<mixed> $data
	 *
	 * @return T
	 *
	 * @throws Exceptions\Runtime
	 */
	public function create(
		string $entity,
		array $data,
	): Entities\API\Entity|Entities\Messages\Entity|Entities\Clients\Entity|Entities\Commands\Entity|Entities\Uiid\Entity
	{
		if (
			(
				$entity === Entities\API\Sockets\DeviceStateEvent::class
				|| $entity === Entities\API\Cloud\Device::class
				|| $entity === Entities\API\Cloud\DeviceState::class
				|| $entity === Entities\API\Cloud\Group::class
			)
			&& array_key_exists('params', $data)
		) {
			$data['state'] = $this->createUuid($data['params']);
		}

		try {
			$options = new ObjectMapper\Processing\Options();
			$options->setAllowUnknownFields();

			return $this->entityMapper->process($data, $entity, $options);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\Runtime('Could not map data to entity: ' . $errorPrinter->printError($ex));
		}
	}

	/**
	 * @param array<mixed> $data
	 *
	 * @throws Exceptions\Runtime
	 */
	private function createUuid(array $data): Entities\Uiid\Entity
	{
		foreach (self::KNOW_UUID_TYPES as $type) {
			try {
				$validated = $this->schemaValidator->validate(Utils\Json::encode($data), $this->getSchema($type));

			} catch (MetadataExceptions\InvalidData) {
				continue;
			} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | Utils\JsonException $ex) {
				throw new Exceptions\Runtime('Could not validate received response payload', $ex->getCode(), $ex);
			}

			$entity = sprintf('\FastyBird\Connector\Sonoff\Entities\Uiid\Uiid%s', $type);
			assert(class_exists($entity));

			try {
				return $this->create(
					$entity,
					(array) Utils\Json::decode(Utils\Json::encode($validated), Utils\Json::FORCE_ARRAY),
				);
			} catch (Exceptions\Runtime $ex) {
				throw new Exceptions\Runtime('Could not map data to entity', $ex->getCode(), $ex);
			} catch (Utils\JsonException $ex) {
				throw new Exceptions\Runtime('Could not create entity from data', $ex->getCode(), $ex);
			}
		}

		throw new Exceptions\Runtime('Could not map data to entity, unsupported type');
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	private function getSchema(int $type): string
	{
		try {
			$schema = Utils\FileSystem::read(
				Sonoff\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'uiid' . DIRECTORY_SEPARATOR . sprintf(
					'uiid%d.json',
					$type,
				),
			);
		} catch (Nette\IOException) {
			throw new Exceptions\Runtime('Validation schema for UUID could not be loaded');
		}

		return $schema;
	}

}
