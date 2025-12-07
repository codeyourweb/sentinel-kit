<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\SigmaRule;

/**
 * Elastalert Clear Command removes all Elastalert rules and Sigma rules.
 * 
 * This console command completely clears all Elastalert rule files and
 * associated Sigma rules from the system. This is a destructive operation
 * intended for debugging and development purposes only.
 * 
 * WARNING: This command will disable all active monitoring and alerting!
 */
#[AsCommand(
    name: 'app:elastalert:clear',
    description: 'Clear all Elastalert rules from the backend application',
)]
class ElastalertClearCommand extends Command
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
     * Configure command options.
     * 
     * Defines the force option to bypass confirmation prompts.
     */
    protected function configure(): void
    {
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force deletion without confirmation'
        );
    }

    /**
     * Execute the Elastalert clearing command.
     * 
     * Removes all Elastalert rule files and deletes all Sigma rules from the database.
     * Includes safety confirmation unless --force option is used.
     * 
     * @param InputInterface $input Command line input options
     * @param OutputInterface $output Command line output interface
     * 
     * @return int Command exit code (SUCCESS or FAILURE)
     * 
     * Options:
     * - --force, -f: Skip confirmation prompt for automated usage
     * 
     * WARNING: This is a destructive operation that:
     * - Deletes all Elastalert rule files from /detection-rules/elastalert/
     * - Removes all Sigma rules from the database
     * - Disables all active monitoring and alerting
     * 
     * Exit codes:
     * - Command::SUCCESS (0): Rules cleared successfully or operation cancelled
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize console style helper and get options
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');

        // Count existing Elastalert files for confirmation
        $elastalertDir = '/detection-rules/elastalert';
        $files = glob($elastalertDir . '/*.yml');
        $fileCount = count($files);

        // Show warning and require confirmation unless forced
        if (!$force) {
            $io->warning([
                'This command clears ALL ElastAlert rules and should ONLY be used for debugging purposes!',
                "ElastAlert files to be deleted: $fileCount",
                'This operation will disrupt active monitoring and alerting.',
                'Use this command only in development or debugging scenarios.'
            ]);

            if (!$io->confirm('Are you sure you want to proceed with clearing all ElastAlert rules?', false)) {
                $io->text('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        // Clear all Elastalert rule files from filesystem
        $this->clearElastalertDirectory();

        // Remove all Sigma rules from database
        $rules = $this->entityManager->getRepository(SigmaRule::class)->findAll();
        foreach ($rules as $rule) {
            $this->entityManager->remove($rule);
        }
        $this->entityManager->flush();

        $io->success("$fileCount ElastAlert rules have been cleared successfully.");
        return Command::SUCCESS;
    }

    /**
     * Clear all Elastalert rule files from the filesystem.
     * 
     * Removes all .yml files from the Elastalert rules directory.
     * This is a destructive operation that cannot be undone.
     */
    private function clearElastalertDirectory(): void
    {
        $elastalertDir = '/detection-rules/elastalert';
        $files = glob($elastalertDir . '/*.yml');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}
