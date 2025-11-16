<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use App\Entity\SigmaRule;
use App\Entity\SigmaRuleVersion;

#[AsCommand(
    name: 'app:sigma:load-rules',
    description: 'Loads *.yml rules files into Sentinel-Kit database in the backend application',
)]
class SigmaRulesLoadCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
       
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

                $yamlData = Yaml::parse($content);
                
                if ($yamlData === null) {
                    $io->warning("File contains no valid YAML data: $filePath");
                    continue;
                }

                $this->storeSigmaRule($content, $yamlData, $relativePath, $io);
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
            return Command::FAILURE;
        } else {
            $io->success("Sigma rules loaded successfully.");
        }


        return Command::SUCCESS;
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
    private function storeSigmaRule(string $content, array $yamlData, string $filePath,SymfonyStyle $io): void
    {
        $slugger = new AsciiSlugger();
        $title = '';
        $description = null;
        $filename = $slugger->slug(pathinfo($filePath, PATHINFO_FILENAME));
        
        if (!empty($yamlData['title'])) {
            $title = $yamlData['title'];
        }else{
            $title = substr($filename, 0, strlen($filename) - 4);
        }

        if (!empty($yamlData['description'])) {
            $description = $yamlData['description'];
        }

        $r = $this->entityManager->GetRepository(SigmaRule::class)->findOneBy(['title' => $title]);
        if ($r) {
            $io->warning(sprintf('Rule with title "%s" already exists already exists in database', $title));
            return;
        }

        $rd = $this->entityManager->getRepository(SigmaRuleVersion::class)->findOneBy(['hash' => md5($content)]);
        if ($rd) {
            $io->warning(sprintf('Rule "%s" ignored - content already exists in %s', $filePath, $title, $description));
            return;
        }

        $rule = new SigmaRule();
        $rule->setFilename($filename);
        $rule->setTitle($title);
        $rule->setDescription($description);
        $rule->setActive(false);
        
        $ruleVersion = new SigmaRuleVersion();
        $ruleVersion->setContent($content);
        $rule->addVersion($ruleVersion);
        
        try{
            $this->entityManager->persist($rule);
        }catch(\Exception $e){
            $io->error(sprintf("Error storing rule: %s - %s", $filePath, $e->getMessage()));
            return;
        }

        return;
    }
}