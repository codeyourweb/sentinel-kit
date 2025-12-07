<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Datasource;

/**
 * Datasource Delete Command removes data sources from the system.
 * 
 * This console command allows administrators to permanently delete data source
 * configurations. Can delete by either ID or name for flexible administration.
 */
#[AsCommand(
    name: 'app:datasource:delete',
    description: 'Delete a datasource from the backend application',
)]
class DatasourceDeleteCommand extends Command
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
     * Configure command arguments.
     * 
     * Defines the required ID or name argument for datasource deletion.
     */
    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'ID or name of the datasource to delete');
    }

    /**
     * Execute the datasource deletion command.
     * 
     * Permanently removes a datasource configuration from the system.
     * Supports lookup by both numeric ID and string name.
     * 
     * @param InputInterface $input Command line input arguments
     * @param OutputInterface $output Command line output interface
     * 
     * @return int Command exit code (SUCCESS or FAILURE)
     * 
     * Arguments:
     * - id: Datasource ID (numeric) or name (string) to delete
     * 
     * Exit codes:
     * - Command::SUCCESS (0): Datasource deleted successfully
     * - Command::FAILURE (1): Datasource not found or database error
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize console style helper
        $io = new SymfonyStyle($input, $output);

        $dsId = $input->getArgument('id');

        // Determine if searching by ID (numeric) or name (string)
        if (is_numeric(intval($dsId)) && intval($dsId) > 0) {
            $datasource = $this->entityManager->getRepository(Datasource::class)->find((int)$dsId);
        } else {
            $datasource = $this->entityManager->getRepository(Datasource::class)->findOneBy(['name' => $dsId]);
        }

        if (!$datasource) {
            $io->error('Datasource not found.');
            return Command::FAILURE;
        }

        // Permanently delete the datasource from database
        try{
            $this->entityManager->remove($datasource);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $io->error('An error occurred while deleting the datasource: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->success('Datasource deleted successfully.');

        return Command::SUCCESS;
    }
}
