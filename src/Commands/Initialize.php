<?php declare(strict_types = 1);

/**
 * Initialize.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           14.05.23
 */

namespace FastyBird\Connector\Sonoff\Commands;

use Doctrine\DBAL;
use Doctrine\Persistence;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette\Utils;
use Psr\Log;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function array_key_exists;
use function array_search;
use function array_values;
use function assert;
use function count;
use function sprintf;
use function strval;
use function usort;

/**
 * Connector initialize command
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Initialize extends Console\Command\Command
{

	public const NAME = 'fb:sonoff-connector:initialize';

	private const CHOICE_QUESTION_CREATE_CONNECTOR = 'Create new connector configuration';

	private const CHOICE_QUESTION_EDIT_CONNECTOR = 'Edit existing connector configuration';

	private const CHOICE_QUESTION_DELETE_CONNECTOR = 'Delete existing connector configuration';

	private const CHOICE_QUESTION_AUTO_MODE = 'Automatic mode';

	private const CHOICE_QUESTION_LAN_MODE = 'Lan network mode';

	private const CHOICE_QUESTION_CLOUD_MODE = 'Cloud server mode';

	private const CHOICE_QUESTION_EUROPE_DC = 'Europe';

	private const CHOICE_QUESTION_AMERICA_DC = 'America';

	private const CHOICE_QUESTION_ASIA_DC = 'Asia';

	private const CHOICE_QUESTION_CHINA_DC = 'China';

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Connectors\ConnectorsManager $connectorsManager,
		private readonly DevicesModels\Connectors\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Connectors\Properties\PropertiesManager $propertiesManager,
		private readonly Persistence\ManagerRegistry $managerRegistry,
		Log\LoggerInterface|null $logger = null,
		string|null $name = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();

		parent::__construct($name);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function configure(): void
	{
		$this
			->setName(self::NAME)
			->setDescription('Sonoff connector initialization');
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->title('Sonoff connector - initialization');

		$io->note('This action will create|update|delete connector configuration.');

		if ($input->getOption('no-interaction') === false) {
			$question = new Console\Question\ConfirmationQuestion(
				'Would you like to continue?',
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if (!$continue) {
				return Console\Command\Command::SUCCESS;
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			'What would you like to do?',
			[
				0 => self::CHOICE_QUESTION_CREATE_CONNECTOR,
				1 => self::CHOICE_QUESTION_EDIT_CONNECTOR,
				2 => self::CHOICE_QUESTION_DELETE_CONNECTOR,
			],
		);

		$question->setErrorMessage('Selected answer: "%s" is not valid.');

		$whatToDo = $io->askQuestion($question);

		if ($whatToDo === self::CHOICE_QUESTION_CREATE_CONNECTOR) {
			$this->createNewConfiguration($io);

		} elseif ($whatToDo === self::CHOICE_QUESTION_EDIT_CONNECTOR) {
			$this->editExistingConfiguration($io);

		} elseif ($whatToDo === self::CHOICE_QUESTION_DELETE_CONNECTOR) {
			$this->deleteExistingConfiguration($io);
		}

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function createNewConfiguration(Style\SymfonyStyle $io): void
	{
		$mode = $this->askMode($io);

		$question = new Console\Question\Question('Provide connector identifier');

		$question->setValidator(function (string|null $answer) {
			if ($answer !== '' && $answer !== null) {
				$findConnectorQuery = new DevicesQueries\FindConnectors();
				$findConnectorQuery->byIdentifier($answer);

				if ($this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\SonoffConnector::class,
				) !== null) {
					throw new Exceptions\Runtime('This identifier is already used');
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'sonoff-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findConnectorQuery = new DevicesQueries\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				if ($this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\SonoffConnector::class,
				) === null) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error('Connector identifier have to provided');

			return;
		}

		$name = $this->askName($io);

		$username = $this->askUsername($io);

		$password = $this->askPassword($io);

		$dataCentre = $this->askCloudApiEndpoint($io);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$connector = $this->connectorsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\SonoffConnector::class,
				'identifier' => $identifier,
				'name' => $name === '' ? null : $name,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE,
				'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'value' => $mode->getValue(),
				'format' => [Types\ClientMode::MODE_LAN, Types\ClientMode::MODE_CLOUD, Types\ClientMode::MODE_AUTO],
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_USERNAME,
				'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::IDENTIFIER_USERNAME),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $username,
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_PASSWORD,
				'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::IDENTIFIER_PASSWORD),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $password,
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_REGION,
				'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::IDENTIFIER_REGION),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'value' => $dataCentre->getValue(),
				'format' => [
					Types\CloudApiEndpoint::ENDPOINT_CHINA,
					Types\CloudApiEndpoint::ENDPOINT_AMERICA,
					Types\CloudApiEndpoint::ENDPOINT_EUROPE,
					Types\CloudApiEndpoint::ENDPOINT_ASIA,
				],
				'connector' => $connector,
			]));

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(sprintf(
				'New connector "%s" was successfully created',
				$connector->getName() ?? $connector->getIdentifier(),
			));
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'initialize-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error('Something went wrong, connector could not be created. Error was logged.');
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function editExistingConfiguration(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->warning('No Sonoff connectors registered in system');

			$question = new Console\Question\ConfirmationQuestion(
				'Would you like to create new Sonoff connector configuration?',
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createNewConfiguration($io);
			}

			return;
		}

		$findConnectorPropertyQuery = new DevicesQueries\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE);

		$modeProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		if ($modeProperty === null) {
			$changeMode = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				'Do you want to change connector devices support?',
				false,
			);

			$changeMode = (bool) $io->askQuestion($question);
		}

		$mode = null;

		if ($changeMode) {
			$mode = $this->askMode($io);
		}

		$name = $this->askName($io, $connector);

		$enabled = $connector->isEnabled();

		if ($connector->isEnabled()) {
			$question = new Console\Question\ConfirmationQuestion(
				'Do you want to disable connector?',
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = false;
			}
		} else {
			$question = new Console\Question\ConfirmationQuestion(
				'Do you want to enable connector?',
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = true;
			}
		}

		$username = $password = null;

		$findConnectorPropertyQuery = new DevicesQueries\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_USERNAME);

		$usernameProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		if ($usernameProperty === null) {
			$changeUsername = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				'Do you want to change connector cloud Access ID?',
				false,
			);

			$changeUsername = (bool) $io->askQuestion($question);
		}

		if ($changeUsername) {
			$username = $this->askUsername($io);
		}

		$findConnectorPropertyQuery = new DevicesQueries\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_PASSWORD);

		$passwordProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		if ($passwordProperty === null) {
			$changePassword = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				'Do you want to change connector cloud Access Secret?',
				false,
			);

			$changePassword = (bool) $io->askQuestion($question);
		}

		if ($changePassword) {
			$password = $this->askPassword($io);
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$connector = $this->connectorsManager->update($connector, Utils\ArrayHash::from([
				'name' => $name === '' ? null : $name,
				'enabled' => $enabled,
			]));

			if ($modeProperty === null) {
				if ($mode === null) {
					$mode = $this->askMode($io);
				}

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE,
					'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
					'value' => $mode->getValue(),
					'format' => [Types\ClientMode::MODE_LAN, Types\ClientMode::MODE_CLOUD, Types\ClientMode::MODE_AUTO],
					'connector' => $connector,
				]));
			} elseif ($mode !== null) {
				$this->propertiesManager->update($modeProperty, Utils\ArrayHash::from([
					'value' => $mode->getValue(),
				]));
			}

			if ($usernameProperty === null) {
				if ($username === null) {
					$username = $this->askUsername($io);
				}

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_USERNAME,
					'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::IDENTIFIER_USERNAME),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $username,
					'connector' => $connector,
				]));
			} elseif ($username !== null) {
				$this->propertiesManager->update($usernameProperty, Utils\ArrayHash::from([
					'value' => $username,
				]));
			}

			if ($passwordProperty === null) {
				if ($password === null) {
					$password = $this->askPassword($io);
				}

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_PASSWORD,
					'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::IDENTIFIER_PASSWORD),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $password,
					'connector' => $connector,
				]));
			} elseif ($password !== null) {
				$this->propertiesManager->update($passwordProperty, Utils\ArrayHash::from([
					'value' => $password,
				]));
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(sprintf(
				'Connector "%s" was successfully updated',
				$connector->getName() ?? $connector->getIdentifier(),
			));
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'initialize-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error('Something went wrong, connector could not be updated. Error was logged.');
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function deleteExistingConfiguration(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info('No Sonoff connectors registered in system');

			return;
		}

		$question = new Console\Question\ConfirmationQuestion(
			'Would you like to continue?',
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->connectorsManager->delete($connector);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(sprintf(
				'Connector "%s" was successfully removed',
				$connector->getName() ?? $connector->getIdentifier(),
			));
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'initialize-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error('Something went wrong, connector could not be removed. Error was logged.');
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}
	}

	private function askMode(Style\SymfonyStyle $io): Types\ClientMode
	{
		$question = new Console\Question\ChoiceQuestion(
			'In what mode should this connector communicate with devices?',
			[
				self::CHOICE_QUESTION_AUTO_MODE,
				self::CHOICE_QUESTION_LAN_MODE,
				self::CHOICE_QUESTION_CLOUD_MODE,
			],
			0,
		);

		$question->setErrorMessage('Selected answer: "%s" is not valid.');
		$question->setValidator(static function (string|null $answer): Types\ClientMode {
			if ($answer === null) {
				throw new Exceptions\InvalidState('Selected answer is not valid');
			}

			if ($answer === self::CHOICE_QUESTION_AUTO_MODE || $answer === '0') {
				return Types\ClientMode::get(Types\ClientMode::MODE_AUTO);
			}

			if ($answer === self::CHOICE_QUESTION_LAN_MODE || $answer === '1') {
				return Types\ClientMode::get(Types\ClientMode::MODE_LAN);
			}

			if ($answer === self::CHOICE_QUESTION_CLOUD_MODE || $answer === '2') {
				return Types\ClientMode::get(Types\ClientMode::MODE_CLOUD);
			}

			throw new Exceptions\InvalidState('Selected answer is not valid');
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\ClientMode);

		return $answer;
	}

	private function askName(Style\SymfonyStyle $io, Entities\SonoffConnector|null $connector = null): string|null
	{
		$question = new Console\Question\Question('Provide connector name', $connector?->getName());

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	private function askUsername(Style\SymfonyStyle $io): string
	{
		$question = new Console\Question\Question('Provide eWelink username');
		$question->setValidator(static function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime('You have to provide valid eWelink username');
			}

			return $answer;
		});

		return strval($io->askQuestion($question));
	}

	private function askPassword(Style\SymfonyStyle $io): string
	{
		$question = new Console\Question\Question('Provide eWelink password');
		$question->setValidator(static function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime('You have to provide valid eWelink password');
			}

			return $answer;
		});

		return strval($io->askQuestion($question));
	}

	private function askCloudApiEndpoint(Style\SymfonyStyle $io): Types\CloudApiEndpoint
	{
		$question = new Console\Question\ChoiceQuestion(
			'Provide which cloud data center you are using?',
			[
				0 => self::CHOICE_QUESTION_EUROPE_DC,
				1 => self::CHOICE_QUESTION_AMERICA_DC,
				2 => self::CHOICE_QUESTION_ASIA_DC,
				3 => self::CHOICE_QUESTION_CHINA_DC,
			],
			0,
		);
		$question->setErrorMessage('Selected answer: "%s" is not valid.');
		$question->setValidator(static function (string|null $answer): Types\CloudApiEndpoint {
			if ($answer === null) {
				throw new Exceptions\InvalidState('Selected answer is not valid');
			}

			if ($answer === self::CHOICE_QUESTION_EUROPE_DC || $answer === '0') {
				return Types\CloudApiEndpoint::get(Types\CloudApiEndpoint::ENDPOINT_EUROPE);
			}

			if ($answer === self::CHOICE_QUESTION_AMERICA_DC || $answer === '1') {
				return Types\CloudApiEndpoint::get(Types\CloudApiEndpoint::ENDPOINT_AMERICA);
			}

			if ($answer === self::CHOICE_QUESTION_ASIA_DC || $answer === '2') {
				return Types\CloudApiEndpoint::get(Types\CloudApiEndpoint::ENDPOINT_ASIA);
			}

			if ($answer === self::CHOICE_QUESTION_CHINA_DC || $answer === '3') {
				return Types\CloudApiEndpoint::get(Types\CloudApiEndpoint::ENDPOINT_CHINA);
			}

			throw new Exceptions\InvalidState('Selected answer is not valid');
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\CloudApiEndpoint);

		return $answer;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): Entities\SonoffConnector|null
	{
		$connectors = [];

		$findConnectorsQuery = new DevicesQueries\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\SonoffConnector::class,
		);
		usort(
			$systemConnectors,
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			static fn (DevicesEntities\Connectors\Connector $a, DevicesEntities\Connectors\Connector $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($systemConnectors as $connector) {
			assert($connector instanceof Entities\SonoffConnector);

			$connectors[$connector->getIdentifier()] = $connector->getIdentifier()
				. ($connector->getName() !== null ? ' [' . $connector->getName() . ']' : '');
		}

		if (count($connectors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			'Please select connector under which you want to manage devices',
			array_values($connectors),
			count($connectors) === 1 ? 0 : null,
		);
		$question->setErrorMessage('Selected connector: "%s" is not valid.');
		$question->setValidator(function (string|null $answer) use ($connectors): Entities\SonoffConnector {
			if ($answer === null) {
				throw new Exceptions\InvalidState('Selected answer is not valid');
			}

			if (array_key_exists($answer, array_values($connectors))) {
				$answer = array_values($connectors)[$answer];
			}

			$identifier = array_search($answer, $connectors, true);

			if ($identifier !== false) {
				$findConnectorQuery = new DevicesQueries\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\SonoffConnector::class,
				);
				assert($connector instanceof Entities\SonoffConnector || $connector === null);

				if ($connector !== null) {
					return $connector;
				}
			}

			throw new Exceptions\InvalidState('Selected answer is not valid');
		});

		$connector = $io->askQuestion($question);
		assert($connector instanceof Entities\SonoffConnector);

		return $connector;
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	private function getOrmConnection(): DBAL\Connection
	{
		$connection = $this->managerRegistry->getConnection();

		if ($connection instanceof DBAL\Connection) {
			return $connection;
		}

		throw new Exceptions\Runtime('Transformer manager could not be loaded');
	}

}
