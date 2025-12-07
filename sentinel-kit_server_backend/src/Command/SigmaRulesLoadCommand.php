<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use App\Entity\SigmaRule;
use App\Entity\SigmaRuleVersion;
use App\Service\SigmaRuleValidator;
use Symfony\Component\Console\Input\InputOption;

/**
 * Sigma Rules Load Command imports YAML rule files into the database.
 * 
 * This command scans the Sigma rules directory for YAML files and imports
 * them into the database as Sigma rules with versioning support. It validates
 * each rule before import and provides comprehensive error reporting.
 * 
 * Features:
 * - Recursive directory scanning for YAML files
 * - YAML validation and parsing
 * - Sigma rule content validation
 * - Duplicate detection and prevention
 * - Auto-enable option for imported rules
 * - Automatic Elastalert synchronization after import
 */
#[AsCommand(
    name: 'app:sigma:load-rules',
    description: 'Loads *.yml rules files into Sentinel-Kit database in the backend application',
)]
class SigmaRulesLoadCommand extends Command
{
    /**
     * Entity manager for database operations.
     */
    private $entityManager;
    
    /**
     * Sigma rule validator for content validation.
     */
    private $validator;

    /**
     * Command constructor with dependency injection.
     * 
     * @param EntityManagerInterface $entityManager Doctrine entity manager
     * @param SigmaRuleValidator $validator Sigma rule content validator
     */
    public function __construct(EntityManagerInterface $entityManager, SigmaRuleValidator $validator)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    /**
     * Configure command options.
     * 
     * Sets up the auto-enable option for automatically activating imported rules.
     */
    protected function configure(): void
    {
        $this->addOption(
            'auto-enable',
            null,
            InputOption::VALUE_NONE,
            'Automatically enable imported rules'
        );
    }

    /**
     * Execute the Sigma rules loading command.
     * 
     * Scans the detection rules directory for YAML files and imports them
     * as Sigma rules into the database with comprehensive validation.
     * 
     * @param InputInterface $input Command line input options
     * @param OutputInterface $output Command line output interface
     * 
     * @return int Command exit code (SUCCESS or FAILURE)
     * 
     * Options:
     * - --auto-enable: Automatically activate imported rules for monitoring
     * 
     * Process:
     * 1. Validate source directory exists
     * 2. Recursively scan for YAML files
     * 3. Parse and validate each YAML file
     * 4. Check for duplicates by title and content hash
     * 5. Create SigmaRule and SigmaRuleVersion entities
     * 6. Persist rules to database
     * 7. Synchronize with Elastalert if rules were imported
     * 
     * Validation includes:
     * - YAML syntax validation
     * - Sigma rule schema validation
     * - Required fields checking
     * - Content hash uniqueness
     * 
     * Exit codes:
     * - Command::SUCCESS (0): Import completed successfully
     * - Command::FAILURE (1): Directory error, validation failures, or no rules imported
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize console style helper and get options
        $io = new SymfonyStyle($input, $output);
        $autoEnable = $input->getOption('auto-enable');
       
        // Define source directory for Sigma rule files
        $directory = '/detection-rules/sigma';

        // Validate that the source directory exists
        if (!is_dir($directory)) {
            $io->error("Directory does not exist: $directory");
            return Command::FAILURE;
        }

        // Recursively find all YAML files in the directory
        $yamlFiles = $this->findYamlFiles($directory);

        $processedCount = 0;
        $errorCount = 0;

        // Process each YAML file found
        foreach ($yamlFiles as $filePath) {
            $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $filePath);
            $io->text("Processing: " . $relativePath);
            
            try {
                // Read file content
                $content = file_get_contents($filePath);
                if ($content === false) {
                    $io->error("Could not read file: $filePath");
                    $errorCount++;
                    continue;
                }

                // Generate filename slug for database storage
                $slugger = new AsciiSlugger();
                $filename = $slugger->slug(pathinfo($relativePath, PATHINFO_FILENAME));
                $titleFromFilename = pathinfo($relativePath, PATHINFO_FILENAME);

                // Parse YAML content
                try {
                    $yamlData = Yaml::parse($content);
                } catch (ParseException $e) {
                    $io->error("YAML parse error in file " . $relativePath . ": " . $e->getMessage());
                    $errorCount++;
                    continue;
                }

                // Validate YAML data exists
                if (!$yamlData) {
                    $io->error("Empty or invalid YAML content in file: " . $relativePath);
                    $errorCount++;
                    continue;
                }

                // Set defaults for missing title and description
                if (empty($yamlData['title'])) {
                    $yamlData['title'] = $titleFromFilename;
                }

                if (empty($yamlData['description'])) {
                    $yamlData['description'] = '';
                }

                // Generate complete YAML content with defaults
                $completedContent = Yaml::dump($yamlData);

                // Validate Sigma rule content structure
                $validationResult = $this->validator->validateSigmaRuleContent($completedContent);
                
                // Handle validation errors
                if (isset($validationResult['error'])) {
                    $io->error("Validation error in file " . $relativePath . ": " . $validationResult['error']);
                    $errorCount++;
                    continue;
                }

                // Check for missing required fields
                if (!empty($validationResult['missingFields'])) {
                    $missingFieldsString = implode(", ", $validationResult['missingFields']);
                    $io->warning("File " . $relativePath . " is missing required fields: " . $missingFieldsString . " - Skipping");
                    $errorCount++;
                    continue;
                }
                
                // Store validated rule in database
                $this->storeSigmaRule(Yaml::dump($validationResult['yamlData']), $validationResult['yamlData'], $filename, $relativePath, $autoEnable, $io);
                $processedCount++;
            } catch (ParseException $e) {
                // Handle YAML parsing exceptions
                $io->error("YAML parse error in file " . $relativePath . ": " . $e->getMessage());
                $errorCount++;
            } catch (\Exception $e) {
                // Handle general processing exceptions
                $io->error("Error processing file " . $relativePath . ": " . $e->getMessage());
                $errorCount++;
            }
        }

        // Persist all changes to database
        try{
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $io->error("Error flushing to database: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Display processing summary
        $io->section("Summary");
        $io->text("Total files processed: $processedCount");
        if ($errorCount > 0) {
            $io->text("Files with errors: $errorCount");
        }

        // Handle successful imports with Elastalert synchronization
        if ($processedCount > 0) {
            $io->success("$processedCount Sigma rules loaded successfully.");
            
            // Automatically synchronize with Elastalert
            $io->text("Synchronizing Elastalert rules...");
            $syncCommand = $this->getApplication()->find('app:elastalert:sync');
            $syncInput = new ArrayInput([]);
            $syncReturnCode = $syncCommand->run($syncInput, $output);
            
            // Report synchronization results
            if ($syncReturnCode === Command::SUCCESS) {
                $io->success("Elastalert synchronization completed successfully.");
            } else {
                $io->warning("Elastalert synchronization failed, but Sigma rules were imported successfully.");
            }
            
            // Return appropriate exit code based on error count
            return ($errorCount > 0) ? Command::FAILURE : Command::SUCCESS;
        }

        // Handle case where no rules were imported
        $io->error("No rules were successfully imported.");
        return Command::FAILURE;
    }

    /**
     * Recursively find all YAML files in a directory.
     * 
     * Scans the provided directory and all subdirectories for files
     * with .yml or .yaml extensions.
     * 
     * @param string $directory The root directory to scan
     * 
     * @return array Array of absolute file paths to YAML files
     */
    private function findYamlFiles(string $directory): array
    {
        $yamlFiles = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['yml', 'yaml'])) {
                $yamlFiles[] = $file->getRealPath();
            }
        }

        return $yamlFiles;
    }

    /**
     * Store Sigma Rule and its version into the database.
     * 
     * Creates a new SigmaRule entity with an associated SigmaRuleVersion
     * and persists them to the database. Includes duplicate checking
     * to prevent importing the same rule multiple times.
     * 
     * @param string $content The complete YAML content of the rule
     * @param array $yamlData Parsed YAML data array
     * @param string $filename Slugified filename for the rule
     * @param string $filePath Original file path for error reporting
     * @param bool $autoEnable Whether to automatically activate the rule
     * @param SymfonyStyle $io Console output interface for warnings
     * 
     * Duplicate Prevention:
     * - Checks for existing rules with the same title
     * - Checks for existing rule versions with the same content hash
     * - Skips import with warning if duplicates are found
     */
    private function storeSigmaRule(string $content, array $yamlData, string $filename, string $filePath, bool $autoEnable, SymfonyStyle $io): void
    {
        // Create new Sigma rule entity
        $rule = new SigmaRule();
        $rule->setTitle($yamlData['title']);
        $rule->setDescription($yamlData['description']);
        $rule->setFilename($filename);
        $rule->setActive($autoEnable);
        
        // Create new rule version entity
        $ruleVersion = new SigmaRuleVersion();
        $ruleVersion->setContent($content);
        $ruleVersion->setLevel($yamlData['level']);
        $rule->addVersion($ruleVersion);

        // Check for duplicate rule by title
        $existingRule = $this->entityManager->GetRepository(SigmaRule::class)->findOneBy(['title' => $yamlData['title']]);
        if ($existingRule) {
            $io->warning(sprintf('Rule with title "%s" already exists in database', $yamlData['title']));
            return;
        }

        // Check for duplicate content by hash
        $existingVersion = $this->entityManager->getRepository(SigmaRuleVersion::class)->findOneBy(['hash' => $ruleVersion->getHash()]);
        if ($existingVersion) {
            $io->warning(sprintf('Rule "%s" ignored - content already exists', $filePath));
            return;
        }

        // Persist the new rule to database
        try{
            $this->entityManager->persist($rule);
        }catch(\Exception $e){
            $io->error(sprintf("Error storing rule: %s - %s", $filePath, $e->getMessage()));
            return;
        }

        return;
    }
}