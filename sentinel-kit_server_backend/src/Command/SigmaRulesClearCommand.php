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
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Sigma Rules Clear Command removes all Sigma rules from the database.
 * 
 * This command completely clears all Sigma rules and their versions from
 * the database and also triggers clearing of associated Elastalert rules.
 * This is a destructive operation intended for debugging and development.
 * 
 * WARNING: This will disable all detection rules and alerting capabilities!
 */
#[AsCommand(
    name: 'app:sigma:clear',
    description: 'Clear all Sigma rules from the backend application',
)]
class SigmaRulesClearCommand extends Command
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
     * Execute the Sigma rules clearing command.
     * 
     * Removes all Sigma rules from the database and clears associated Elastalert rules.
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
     * Process:
     * 1. Count existing Sigma rules for confirmation
     * 2. Show warning with rule count and confirm deletion
     * 3. Clear associated Elastalert rules first
     * 4. Remove all Sigma rules and versions from database
     * 5. Report success with deletion count
     * 
     * WARNING: This is a destructive operation that:
     * - Deletes all Sigma rules and their versions from the database
     * - Clears all associated Elastalert rule files
     * - Completely disables detection and alerting capabilities
     * 
     * Exit codes:
     * - Command::SUCCESS (0): Rules cleared successfully or operation cancelled
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize console style helper and get options
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');

        // Count existing Sigma rules for confirmation
        $rules = $this->entityManager->getRepository(SigmaRule::class)->findAll();
        $ruleCount = count($rules);

        // Handle case where no rules exist
        if ($ruleCount === 0) {
            $io->info('No Sigma rules found to clear.');
            return Command::SUCCESS;
        }

        // Show warning and require confirmation unless forced
        $io->warning([
            'This operation will permanently delete ALL Sigma rules!',
            "Total rules to be deleted: $ruleCount",
            'This action is irreversible.'
        ]);

        if (!$force) {
            if (!$io->confirm('Are you sure you want to proceed?', false)) {
                $io->text('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        // Clear associated Elastalert rules first
        $syncCommand = $this->getApplication()->find('app:elastalert:clear');
        $syncInput = new ArrayInput(['--force' => true]);
        $syncCommand->run($syncInput, $output);
        
        // Remove all Sigma rules from the database
        foreach ($rules as $rule) {
            $this->entityManager->remove($rule);
        }

        // Persist changes to database
        $this->entityManager->flush();

        $io->success("$ruleCount Sigma rules have been cleared successfully.");
        return Command::SUCCESS;
    }
}
