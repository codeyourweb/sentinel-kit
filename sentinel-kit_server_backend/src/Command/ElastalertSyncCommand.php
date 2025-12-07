<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ElastalertRuleValidator;
use App\Entity\SigmaRule;

/**
 * Elastalert Sync Command synchronizes Elastalert rules from active Sigma rules.
 * 
 * This command converts active Sigma rules into Elastalert rule files,
 * creating the rule files in the appropriate directory for Elastalert to process.
 * It handles rule validation and automatically deactivates invalid rules.
 * 
 * The synchronization process:
 * 1. Clears existing Elastalert rule files
 * 2. Retrieves all active Sigma rules from the database
 * 3. Validates and converts each rule to Elastalert format
 * 4. Creates YAML rule files in the detection rules directory
 * 5. Deactivates any rules that fail validation
 */
#[AsCommand(
    name: 'app:elastalert:sync',
    description: 'Synchronize Elastalert rules from enabled sigma rules',
)]
class ElastalertSyncCommand extends Command
{
    /**
     * Entity manager for database operations.
     */
    private EntityManagerInterface $entityManager;

    /**
     * Elastalert rule validator and converter service.
     */
    private ElastalertRuleValidator $elastalertValidator;

    /**
     * Command constructor with dependency injection.
     * 
     * @param EntityManagerInterface $entityManager Doctrine entity manager
     * @param ElastalertRuleValidator $elastalertValidator Rule validation and conversion service
     */
    public function __construct(EntityManagerInterface $entityManager, ElastalertRuleValidator $elastalertValidator)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->elastalertValidator = $elastalertValidator;
    }

    /**
     * Configure command options.
     * 
     * No additional options are needed for this command.
     */
    protected function configure(): void
    {
        // No additional configuration required
    }

    /**
     * Execute the Elastalert rule synchronization command.
     * 
     * Converts all active Sigma rules to Elastalert format and creates
     * rule files for the Elastalert service to process.
     * 
     * @param InputInterface $input Command line input options
     * @param OutputInterface $output Command line output interface
     * 
     * @return int Command exit code (SUCCESS or FAILURE)
     * 
     * Process:
     * 1. Clear existing Elastalert rule files from the filesystem
     * 2. Query database for all active Sigma rules
     * 3. Validate and convert each Sigma rule to Elastalert format
     * 4. Create YAML rule files in /detection-rules/elastalert/
     * 5. Deactivate any rules that fail validation
     * 6. Persist database changes
     * 
     * Error Handling:
     * - Invalid rules are automatically deactivated and logged
     * - Database transaction failures trigger directory cleanup
     * - Detailed error messages for troubleshooting
     * 
     * Exit codes:
     * - Command::SUCCESS (0): Synchronization completed successfully
     * - Command::FAILURE (1): Database flush operation failed
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize console style helper
        $io = new SymfonyStyle($input, $output);

        // Clear existing Elastalert rule files to start fresh
        $this->clearElastalertDirectory();

        // Retrieve all active Sigma rules from the database
        $rules = $this->entityManager->getRepository(SigmaRule::class)->findBy(['active' => true]);
        
        // Process each active Sigma rule
        foreach ($rules as $rule) {
            // Attempt to create Elastalert rule from Sigma rule
            $result = $this->elastalertValidator->createElastalertRule($rule->getRuleLatestVersion());
            
            if (isset($result['error'])) {
                // Handle validation errors by deactivating the rule
                $io->error('Failed to create Elastalert rule for Sigma rule "' . $rule->getTitle() . '": ' . $result['error']);
                $rule->setActive(false);
                $this->entityManager->persist($rule);
            } else {
                // Success: rule file created successfully
                $io->writeln('Elastalert rule created for Sigma rule "' . $rule->getTitle() . '" at ' . $result['filePath']);
            }
        }

        // Persist all database changes (rule deactivations)
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Handle database errors with cleanup
            $io->error('Failed to flush changes to database: ' . $e->getMessage());
            $this->clearElastalertDirectory(); // Clean up created files
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Clear all Elastalert rule files from the filesystem.
     * 
     * Removes all .yml files from the Elastalert rules directory
     * to ensure a clean state before synchronization.
     * 
     * This method is called both at the start of synchronization
     * and during error cleanup to maintain consistency.
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
