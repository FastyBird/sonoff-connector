<?php declare(strict_types = 1);

/**
 * Discover.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           06.05.23
 */

namespace FastyBird\Connector\Sonoff\Commands;

use DateTimeInterface;
use FastyBird\Connector\Sonoff\Clients;
use FastyBird\Connector\Sonoff\Consumers;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Psr\EventDispatcher;
use Psr\Log;
use Ramsey\Uuid;
use React\EventLoop;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function array_key_first;
use function array_search;
use function array_values;
use function assert;
use function count;
use function intval;
use function is_string;
use function React\Async\async;
use function sprintf;
use function usort;
use const SIGINT;

/**
 * Connector devices discovery command
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Discover extends Console\Command\Command
{

	public const NAME = 'fb:sonoff-connector:discover';

	private const DISCOVERY_WAITING_INTERVAL = 5.0;

	private const DISCOVERY_MAX_PROCESSING_INTERVAL = 90.0;

	private const QUEUE_PROCESSING_INTERVAL = 0.01;

	private DateTimeInterface|null $executedTime = null;

	private EventLoop\TimerInterface|null $consumerTimer = null;

	private EventLoop\TimerInterface|null $progressBarTimer;

	private Clients\Discovery|null $client = null;

	public function __construct(
		private readonly Clients\DiscoveryFactory $clientFactory,
		private readonly Consumers\Messages $consumer,
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
		string|null $name = null,
	)
	{
		parent::__construct($name);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function configure(): void
	{
		$this
			->setName(self::NAME)
			->setDescription('Sonoff connector devices discovery')
			->setDefinition(
				new Input\InputDefinition([
					new Input\InputOption(
						'connector',
						'c',
						Input\InputOption::VALUE_OPTIONAL,
						'Run devices module connector',
						true,
					),
				]),
			);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws DevicesExceptions\InvalidState
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->title('Sonoff connector - discovery');

		$io->note('This action will run connector devices discovery.');

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

		if (
			$input->hasOption('connector')
			&& is_string($input->getOption('connector'))
			&& $input->getOption('connector') !== ''
		) {
			$connectorId = $input->getOption('connector');

			$findConnectorQuery = new DevicesQueries\FindConnectors();

			if (Uuid\Uuid::isValid($connectorId)) {
				$findConnectorQuery->byId(Uuid\Uuid::fromString($connectorId));
			} else {
				$findConnectorQuery->byIdentifier($connectorId);
			}

			$connector = $this->connectorsRepository->findOneBy($findConnectorQuery, Entities\SonoffConnector::class);
			assert($connector instanceof Entities\SonoffConnector || $connector === null);

			if ($connector === null) {
				$io->warning('Connector was not found in system');

				return Console\Command\Command::FAILURE;
			}
		} else {
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
				$io->warning('No connectors registered in system');

				return Console\Command\Command::FAILURE;
			}

			if (count($connectors) === 1) {
				$connectorIdentifier = array_key_first($connectors);

				$findConnectorQuery = new DevicesQueries\FindConnectors();
				$findConnectorQuery->byIdentifier($connectorIdentifier);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\SonoffConnector::class,
				);
				assert($connector instanceof Entities\SonoffConnector || $connector === null);

				if ($connector === null) {
					$io->warning('Connector was not found in system');

					return Console\Command\Command::FAILURE;
				}

				if ($input->getOption('no-interaction') === false) {
					$question = new Console\Question\ConfirmationQuestion(
						sprintf(
							'Would you like to discover devices with "%s" connector',
							$connector->getName() ?? $connector->getIdentifier(),
						),
						false,
					);

					if ($io->askQuestion($question) === false) {
						return Console\Command\Command::SUCCESS;
					}
				}
			} else {
				$question = new Console\Question\ChoiceQuestion(
					'Please select connector to perform discovery',
					array_values($connectors),
				);

				$question->setErrorMessage('Selected connector: %s is not valid.');

				$connectorIdentifier = array_search($io->askQuestion($question), $connectors, true);

				if ($connectorIdentifier === false) {
					$io->error('Something went wrong, connector could not be loaded');

					$this->logger->alert(
						'Could not read connector identifier from console answer',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
							'type' => 'discovery-cmd',
						],
					);

					return Console\Command\Command::FAILURE;
				}

				$findConnectorQuery = new DevicesQueries\FindConnectors();
				$findConnectorQuery->byIdentifier($connectorIdentifier);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\SonoffConnector::class,
				);
				assert($connector instanceof Entities\SonoffConnector || $connector === null);
			}

			if ($connector === null) {
				$io->error('Something went wrong, connector could not be loaded');

				$this->logger->alert(
					'Connector was not found',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'discovery-cmd',
					],
				);

				return Console\Command\Command::FAILURE;
			}
		}

		if (!$connector->isEnabled()) {
			$io->warning('Connector is disabled. Disabled connector could not be executed');

			return Console\Command\Command::SUCCESS;
		}

		$this->client = $this->clientFactory->create($connector);

		$progressBar = new Console\Helper\ProgressBar(
			$output,
			intval(self::DISCOVERY_MAX_PROCESSING_INTERVAL * 60),
		);

		$progressBar->setFormat('[%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %');

		try {
			$this->eventLoop->addSignal(SIGINT, function () use ($io): void {
				$this->logger->info(
					'Stopping Sonoff connector discovery...',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'discovery-cmd',
					],
				);

				$io->info('Stopping Sonoff connector discovery...');

				$this->client?->disconnect();

				$this->checkAndTerminate();
			});

			$this->eventLoop->futureTick(
				async(function () use ($io, $progressBar): void {
					$this->logger->info(
						'Starting Sonoff connector discovery...',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
							'type' => 'discovery-cmd',
						],
					);

					$io->info('Starting Sonoff connector discovery...');

					$progressBar->start();

					$this->executedTime = $this->dateTimeFactory->getNow();

					$this->client?->on('finished', function (): void {
						$this->client?->disconnect();

						$this->checkAndTerminate();
					});

					try {
						$this->client?->discover();
					} catch (Throwable $ex) {
						if ($this->consumerTimer !== null) {
							$this->eventLoop->cancelTimer($this->consumerTimer);
						}

						$this->dispatcher?->dispatch(
							new DevicesEvents\TerminateConnector(
								MetadataTypes\ConnectorSource::get(
									MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
								),
								'Devices discovery failed',
								$ex,
							),
						);
					}
				}),
			);

			$this->consumerTimer = $this->eventLoop->addPeriodicTimer(
				self::QUEUE_PROCESSING_INTERVAL,
				async(function (): void {
					$this->consumer->consume();
				}),
			);

			$this->progressBarTimer = $this->eventLoop->addPeriodicTimer(
				0.1,
				async(static function () use ($progressBar): void {
					$progressBar->advance();
				}),
			);

			$this->eventLoop->addTimer(
				self::DISCOVERY_MAX_PROCESSING_INTERVAL,
				async(function (): void {
					$this->client?->disconnect();

					$this->checkAndTerminate();
				}),
			);

			$this->eventLoop->run();

			$progressBar->finish();

			$io->newLine();

			$findDevicesQuery = new DevicesQueries\FindDevices();
			$findDevicesQuery->byConnectorId($connector->getId());

			$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\SonoffDevice::class);

			$table = new Console\Helper\Table($output);
			$table->setHeaders([
				'#',
				'ID',
				'Name',
				'Type',
				'Local IP address',
			]);

			$foundDevices = 0;

			foreach ($devices as $device) {
				assert($device instanceof Entities\SonoffDevice);

				$createdAt = $device->getCreatedAt();

				if (
					$createdAt !== null
					&& $this->executedTime !== null
					&& $createdAt->getTimestamp() > $this->executedTime->getTimestamp()
				) {
					$foundDevices++;

					$ipAddress = $device->getIpAddress();
					$ipAddress = $ipAddress !== null ? $ipAddress . ':' . $device->getPort() : null;

					$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
					$findDevicePropertyQuery->forDevice($device);
					$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MODEL);

					$hardwareModelProperty = $this->devicePropertiesRepository->findOneBy($findDevicePropertyQuery);

					$table->addRow([
						$foundDevices,
						$device->getPlainId(),
						$device->getName() ?? $device->getIdentifier(),
						$hardwareModelProperty?->getValue() ?? 'N/A',
						$ipAddress ?? 'N/A',
					]);
				}
			}

			if ($foundDevices > 0) {
				$io->newLine();

				$io->info(sprintf('Found %d new devices', $foundDevices));

				$table->render();

				$io->newLine();

			} else {
				$io->info('No devices were found');
			}

			$io->success('Devices discovery was successfully finished');

			return Console\Command\Command::SUCCESS;
		} catch (DevicesExceptions\Terminate $ex) {
			$this->logger->error(
				'An error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'discovery-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error('Something went wrong, discovery could not be finished. Error was logged.');

			$this->client->disconnect();

			$this->eventLoop->stop();

			return Console\Command\Command::FAILURE;
		} catch (Throwable $ex) {
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
					'type' => 'discovery-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error('Something went wrong, discovery could not be finished. Error was logged.');

			$this->client->disconnect();

			$this->eventLoop->stop();

			return Console\Command\Command::FAILURE;
		}
	}

	private function checkAndTerminate(): void
	{
		if ($this->consumer->isEmpty()) {
			if ($this->consumerTimer !== null) {
				$this->eventLoop->cancelTimer($this->consumerTimer);
			}

			if ($this->progressBarTimer !== null) {
				$this->eventLoop->cancelTimer($this->progressBarTimer);
			}

			$this->eventLoop->stop();

		} else {
			if (
				$this->executedTime !== null
				&& $this->dateTimeFactory->getNow()->getTimestamp() - $this->executedTime->getTimestamp() > self::DISCOVERY_MAX_PROCESSING_INTERVAL
			) {
				$this->logger->error(
					'Discovery exceeded reserved time and have been terminated',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SONOFF,
						'type' => 'discovery-cmd',
					],
				);

				if ($this->consumerTimer !== null) {
					$this->eventLoop->cancelTimer($this->consumerTimer);
				}

				if ($this->progressBarTimer !== null) {
					$this->eventLoop->cancelTimer($this->progressBarTimer);
				}

				$this->eventLoop->stop();

				return;
			}

			$this->eventLoop->addTimer(
				self::DISCOVERY_WAITING_INTERVAL,
				async(function (): void {
					$this->checkAndTerminate();
				}),
			);
		}
	}

}
