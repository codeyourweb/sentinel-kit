<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\SigmaRule;

/**
 * Sigma Rules Export Command exports Sigma rules from database to YAML files.
 * 
 * This command extracts all Sigma rules from the database and writes them
 * as individual YAML files in the detection rules directory. It exports the
 * latest version of each rule for external use or backup purposes.
 * 
 * The export process ensures the target directory is empty to avoid
 * file conflicts and maintains rule integrity during export.
 */
#[AsCommand(
    name: 'app:sigma:export-rules',
    description: 'Exports Sigma rules from Sentinel-Kit database in the backend application to *.yml files',
)]
class SigmaRulesExportCommand extends Command
{
    /**
     * Entity manager for database operations.
     */
    private $entityManager;

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
     * No additional options are required for this command.
     */
    protected function configure(): void
    {
        // No additional configuration required
    }

    /**
     * Execute the Sigma rules export command.
     * 
     * Exports all Sigma rules from the database to individual YAML files
     * in the detection rules directory.
     * 
     * @param InputInterface $input Command line input options
     * @param OutputInterface $output Command line output interface
     * 
     * @return int Command exit code (SUCCESS or FAILURE)
     * 
     * Process:
     * 1. Validate that the target directory exists
     * 2. Ensure the target directory is empty
     * 3. Retrieve all Sigma rules with their latest versions
     * 4. Export each rule as a YAML file using the rule filename
     * 5. Report export statistics
     * 
     * Requirements:
     * - Target directory /detection-rules/sigma must exist
     * - Target directory must be empty before export
     * - Rules must have at least one version to be exported
     * 
     * Error Handling:
     * - Directory validation prevents export conflicts
     * - Rules without versions are skipped with warnings
     * - File write errors are logged but don't halt the process
     * 
     * Exit codes:
     * - Command::SUCCESS (0): Export completed successfully
     * - Command::FAILURE (1): Directory doesn't exist or isn't empty
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize console style helper
        $io = new SymfonyStyle($input, $output);
       
        // Define target directory for exported rules
        $directory = '/detection-rules/sigma';

        // Validate that the target directory exists
        if (!is_dir($directory)) {
            $io->error("Directory does not exist: $directory");
            return Command::FAILURE;
        }

        // Ensure directory is empty to avoid conflicts
        if(count(scandir($directory)) > 2) {
            $io->error("Sigma rules directory is not empty. Clear it before exporting rules.");
            return Command::FAILURE;
        }

        // Retrieve all Sigma rules with their latest versions
        $rules = $this->entityManager->getRepository(SigmaRule::class)->findAllWithLatestRuleVersion();
        
        // Export each rule to a YAML file
        foreach($rules as $rule) {
            $io->writeln("Exporting rule: " . $rule->getTitle());
            $latestVersion = $rule->getVersions()->first();

            // Skip rules without versions
            if (!$latestVersion) {
                $io->warning("Skipping rule '{$rule->getTitle()}' because it has no versions.");
                continue;
            }
            
            // Write rule content to YAML file
            try {
                file_put_contents($directory . '/' . $rule->getFilename() . '.yml', $latestVersion->getContent());
            }
            catch(\Exception $e) {
                $io->error("Error exporting rule '{$rule->getTitle()}': " . $e->getMessage()); 
            }
        }
        
        // Report export success with statistics
        $io->success("Successfully exported " . count($rules) . " Sigma rules.");
        
        return Command::SUCCESS;
    }
}