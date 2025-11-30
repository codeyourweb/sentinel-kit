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

#[AsCommand(
    name: 'app:elastalert:sync',
    description: 'Synchronize Elastalert rules from enabled sigma rules',
)]
class ElastalertSyncCommand extends Command
{

    private EntityManagerInterface $entityManager;
    private ElastalertRuleValidator $elastalertValidator;

    public function __construct(EntityManagerInterface $entityManager, ElastalertRuleValidator $elastalertValidator)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->elastalertValidator = $elastalertValidator;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->clearElastalertDirectory();

        $rules = $this->entityManager->getRepository(SigmaRule::class)->findBy(['active' => true]);
        foreach ($rules as $rule) {
            $e = $this->elastalertValidator->createElastalertRule($rule->getRuleLatestVersion());
            if (isset($e['error'])) {
                $io->error('Failed to create Elastalert rule for Sigma rule "' . $rule->getTitle() . '": ' . $e['error']);
                $rule->setActive(false);
                $this->entityManager->persist($rule);
            } else {
                $io->writeln('Elastalert rule created for Sigma rule "' . $rule->getTitle() . '" at ' . $e['filePath']);
            }
        }

        try{
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $io->error('Failed to flush changes to database: ' . $e->getMessage());
            $this->clearElastalertDirectory();
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function clearElastalertDirectory(): void
    {
        $elastalertDir = '/detection-rules/elastalert';
        $files = glob($elastalertDir . '/*.yml');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}
