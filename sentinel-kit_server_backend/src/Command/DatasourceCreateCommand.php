<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Entity\Datasource;


/**
 * Datasource Create Command creates new log data sources for ingestion.
 * 
 * This console command allows administrators to create new data source configurations
 * for log ingestion. Each data source gets a unique ingest key and can be configured
 * with validity periods and target Elasticsearch indices.
 */
#[AsCommand(
    name: 'app:datasource:create',
    description: 'Create a new ingest datasource in the backend application',
)]
class DatasourceCreateCommand extends Command
{
    /**
     * Entity manager for database operations.
     */
    private $entityManager;
    
    /**
     * URL generator for creating ingest URLs.
     */
    private $urlGenerator;

    /**
     * Command constructor with dependency injection.
     * 
     * @param EntityManagerInterface $entityManager Doctrine entity manager
     * @param UrlGeneratorInterface $urlGenerator URL generator for ingest endpoints
     */
    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Configure command arguments.
     * 
     * Defines required and optional arguments for datasource creation.
     */
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the datasource')
            ->addArgument('index', InputArgument::REQUIRED, 'Target index in Elasticsearch')
            ->addArgument('validFrom', InputArgument::OPTIONAL, '(optional) Valid from (YYYY-MM-DD)')
            ->addArgument('validTo', InputArgument::OPTIONAL, '(optional) Valid to (YYYY-MM-DD)');
    }

    /**
     * Execute the datasource creation command.
     * 
     * Creates a new data source with the specified configuration, generates
     * a unique ingest key, and provides the ingestion endpoint information.
     * 
     * @param InputInterface $input Command line input arguments
     * @param OutputInterface $output Command line output interface
     * 
     * @return int Command exit code (SUCCESS or FAILURE)
     * 
     * Arguments:
     * - name: Unique name for the data source
     * - index: Target Elasticsearch index suffix
     * - validFrom: Optional start date (YYYY-MM-DD format)
     * - validTo: Optional end date (YYYY-MM-DD format)
     * 
     * Output:
     * - Success: Datasource details including ingest key and forwarder URL
     * - Failure: Error message for duplicate names or invalid dates
     * 
     * Exit codes:
     * - Command::SUCCESS (0): Datasource created successfully
     * - Command::FAILURE (1): Name already exists, invalid dates, or database error
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize console style helper and extract arguments
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $index = $input->getArgument('index');
        $validFrom = $input->getArgument('validFrom');
        $validTo = $input->getArgument('validTo');

        $validFromDate = null;
        $validToDate = null;

        // Parse and validate optional date arguments
        if ($validFrom) {
            try{
                $validFromDate = new \DateTime($validFrom);
            }catch (\Exception $e){
                $io->error('Invalid date format for validFrom. Please use YYYY-MM-DD.');
                return Command::FAILURE;
            }
        }

        if ($validTo) {
            try {
                $validToDate = new \DateTime($validTo);
            }catch (\Exception $e){
                $io->error('Invalid date format for validTo. Please use YYYY-MM-DD.');
                return Command::FAILURE;
            }
        }

        // Check for existing datasource with the same name
        $existingDatasource = $this->entityManager->getRepository(Datasource::class)->findOneBy(['name' => $name]);

        if ($existingDatasource) {
            $io->error(sprintf('Datasource "%s" already exists.', $name));
            return Command::FAILURE;
        }

        // Create new datasource entity with provided configuration
        $datasource = new Datasource();
        $output->writeln(sprintf('%s - %s', $name, $index));
        $datasource->setName($name);
        $datasource->setTargetIndex($index);
        $datasource->setValidFrom($validFromDate);
        $datasource->setValidTo($validToDate);

        // Persist the new datasource to database
        try {
            $this->entityManager->persist($datasource);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $io->error('An error occurred while creating the datasource: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Display success information with datasource details
        $io->success(sprintf('Datasource "%s" created successfully', $name));
        $io->writeln(sprintf('Valid from %s', $validFrom ? $validFromDate->format('Y-m-d') : 'N/A'));
        $io->writeln(sprintf('Valid to %s', $validTo ? $validToDate->format('Y-m-d') : 'N/A'));
        $io->writeln(sprintf('Ingest key (header X-Ingest-Key): %s', $datasource->getIngestKey()));
        $io->writeln(sprintf('Forwarder URL: %s', $this->urlGenerator->generate('app_ingest_json', [], UrlGeneratorInterface::ABSOLUTE_URL)));

        return Command::SUCCESS;
    }
}