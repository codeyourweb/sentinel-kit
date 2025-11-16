<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use App\Entity\SigmaRule;
use App\Entity\SigmaRuleVersion;

#[AsCommand(
    name: 'app:sigma:export-rules',
    description: 'Exports Sigma rules from Sentinel-Kit database in the backend application to *.yml files',
)]
class SigmaRulesExportCommand extends Command
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

        if(count(scandir($directory)) > 2) {
            $io->error("Sigma rules directory is not empty. Clear it before exporting rules.");
            return Command::FAILURE;
        }

        $rules = $this->entityManager->getRepository(SigmaRule::class)->findAllWithLatestRuleVersion();
          foreach($rules as $rule) {
            $io->writeln("Exporting rule: " . $rule->getTitle());
            $latestVersion = $rule->getVersions()->first();

            if (!$latestVersion) {
                $io->warning("Skipping rule '{$rule->getTitle()}' because it has no versions.");
                continue;
            }
            
            try {
                file_put_contents($directory . '/' . $rule->getFilename() . '.yml', $latestVersion->getContent());
            }
            catch(\Exception $e) {
                $io->error("Error exporting rule '{$rule->getTitle()}': " . $e->getMessage()); 
            }
        }
        
        $io->success("Successfully exported " . count($rules) . " Sigma rules.");
        
        return Command::SUCCESS;
    }
}