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

#[AsCommand(
    name: 'app:sigma:load-rules',
    description: 'Loads *.yml rules files into Sentinel-Kit database in the backend application',
)]
class SigmaRulesLoadCommand extends Command
{
    private $entityManager;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, SigmaRuleValidator $validator)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    protected function configure(): void
    {
        $this->addOption(
            'auto-enable',
            null,
            InputOption::VALUE_NONE,
            'Automatically enable imported rules'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $autoEnable = $input->getOption('auto-enable');
       
        $directory = '/detection-rules/sigma';

        if (!is_dir($directory)) {
            $io->error("Directory does not exist: $directory");
            return Command::FAILURE;
        }

        $yamlFiles = $this->findYamlFiles($directory);

        $processedCount = 0;
        $errorCount = 0;

        foreach ($yamlFiles as $filePath) {
            $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $filePath);
            $io->text("Processing: " . $relativePath);
            
            try {
                $content = file_get_contents($filePath);
                if ($content === false) {
                    $io->error("Could not read file: $filePath");
                    $errorCount++;
                    continue;
                }

                $slugger = new AsciiSlugger();
                $filename = $slugger->slug(pathinfo($relativePath, PATHINFO_FILENAME));
                $titleFromFilename = pathinfo($relativePath, PATHINFO_FILENAME);

                try {
                    $yamlData = Yaml::parse($content);
                } catch (ParseException $e) {
                    $io->error("YAML parse error in file " . $relativePath . ": " . $e->getMessage());
                    $errorCount++;
                    continue;
                }

                if (!$yamlData) {
                    $io->error("Empty or invalid YAML content in file: " . $relativePath);
                    $errorCount++;
                    continue;
                }

                if (empty($yamlData['title'])) {
                    $yamlData['title'] = $titleFromFilename;
                }

                if (empty($yamlData['description'])) {
                    $yamlData['description'] = '';
                }

                $completedContent = Yaml::dump($yamlData);

                $validationResult = $this->validator->validateSigmaRuleContent($completedContent);
                
                if (isset($validationResult['error'])) {
                    $io->error("Validation error in file " . $relativePath . ": " . $validationResult['error']);
                    $errorCount++;
                    continue;
                }

                if (!empty($validationResult['missingFields'])) {
                    $missingFieldsString = implode(", ", $validationResult['missingFields']);
                    $io->warning("File " . $relativePath . " is missing required fields: " . $missingFieldsString . " - Skipping");
                    $errorCount++;
                    continue;
                }
                
                $this->storeSigmaRule(Yaml::dump($validationResult['yamlData']), $validationResult['yamlData'], $filename, $relativePath, $autoEnable, $io);
                $processedCount++;
            } catch (ParseException $e) {
                $io->error("YAML parse error in file " . $relativePath . ": " . $e->getMessage());
                $errorCount++;
            } catch (\Exception $e) {
                $io->error("Error processing file " . $relativePath . ": " . $e->getMessage());
                $errorCount++;
            }
        }

        try{
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $io->error("Error flushing to database: " . $e->getMessage());
            return Command::FAILURE;
        }

        $io->section("Summary");
        $io->text("Total files processed: $processedCount");
        if ($errorCount > 0) {
            $io->text("Files with errors: $errorCount");
        }

        if ($processedCount > 0) {
            $io->success("$processedCount Sigma rules loaded successfully.");
            
            $io->text("Synchronizing Elastalert rules...");
            $syncCommand = $this->getApplication()->find('app:elastalert:sync');
            $syncInput = new ArrayInput([]);
            $syncReturnCode = $syncCommand->run($syncInput, $output);
            
            if ($syncReturnCode === Command::SUCCESS) {
                $io->success("Elastalert synchronization completed successfully.");
            } else {
                $io->warning("Elastalert synchronization failed, but Sigma rules were imported successfully.");
            }
            
            return ($errorCount > 0) ? Command::FAILURE : Command::SUCCESS;
        }

        $io->error("No rules were successfully imported.");
        return Command::FAILURE;
    }

    /**
     * Recursively find all YAML files in a directory
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
     * Store Sigma Rule and its version into the database
     */
    private function storeSigmaRule(string $content, array $yamlData, string $filename, string $filePath, bool $autoEnable, SymfonyStyle $io): void
    {

        $rule = new SigmaRule();
        $rule->setTitle($yamlData['title']);
        $rule->setDescription($yamlData['description']);
        $rule->setFilename($filename);
        $rule->setActive($autoEnable);
        
        $ruleVersion = new SigmaRuleVersion();
        $ruleVersion->setContent($content);
        $ruleVersion->setLevel($yamlData['level']);
        $rule->addVersion($ruleVersion);

        $r = $this->entityManager->GetRepository(SigmaRule::class)->findOneBy(['title' => $yamlData['title']]);
        if ($r) {
            $io->warning(sprintf('Rule with title "%s" already exists in database', $yamlData['title']));
            return;
        }

        $rd = $this->entityManager->getRepository(SigmaRuleVersion::class)->findOneBy(['hash' => $ruleVersion->getHash()]);
        if ($rd) {
            $io->warning(sprintf('Rule "%s" ignored - content already exists', $filePath));
            return;
        }

        
        try{
            $this->entityManager->persist($rule);
        }catch(\Exception $e){
            $io->error(sprintf("Error storing rule: %s - %s", $filePath, $e->getMessage()));
            return;
        }

        return;
    }
}