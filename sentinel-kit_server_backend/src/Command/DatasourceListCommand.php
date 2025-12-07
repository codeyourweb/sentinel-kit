<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Datasource;

/**
 * Datasource List Command displays all configured data sources.
 * 
 * This console command allows administrators to view all registered data sources
 * with their configuration details including validity periods and target indices.
 */
#[AsCommand(
    name: 'app:datasource:list',
    description: 'List all data sources in the backend application',
)]
class DatasourceListCommand extends Command
{
    /**
     * Entity manager for database operations.
     */
    private EntityManagerInterface $entityManager;

    /**
     * Command constructor with dependency injection.
     * 
     * @param EntityManagerInterface $entityManager Doctrine entity manager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    /**
     * Configure command (no arguments needed for listing all datasources).
     */
    protected function configure(): void
    {
    }

    /**
     * Execute the datasource listing command.
     * 
     * Retrieves and displays all data source configurations with their
     * details including ID, name, target index, and validity periods.
     * 
     * @param InputInterface $input Command line input (no arguments)
     * @param OutputInterface $output Command line output interface
     * 
     * @return int Command exit code (always SUCCESS)
     * 
     * Output format: "ID: {id} | Name: {name} | Index: {index} | Valid From: {date} | Valid To: {date}"
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize console style helper
        $io = new SymfonyStyle($input, $output);

        // Retrieve all datasources from database
        $datasources = $this->entityManager->getRepository(Datasource::class)->findAll();
        
        // Display each datasource's configuration details
        foreach ($datasources as $datasource) {
            $io->writeln(
                'ID: ' . $datasource->getId() . 
                ' | Name: ' . $datasource->getName() . 
                ' | Index: ' . $datasource->getTargetIndex() .
                ' | Valid From: ' . ($datasource->getValidFrom() ? $datasource->getValidFrom()->format('Y-m-d') : 'N/A') .
                ' | Valid To: ' . ($datasource->getValidTo() ? $datasource->getValidTo()->format('Y-m-d') : 'N/A')
            );
        }

        return Command::SUCCESS;
    }
}
