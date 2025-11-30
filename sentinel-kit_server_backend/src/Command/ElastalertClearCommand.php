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

#[AsCommand(
    name: 'app:elastalert:clear',
    description: 'Clear all Elastalert rules from the backend application',
)]
class ElastalertClearCommand extends Command
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force deletion without confirmation'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');

        $elastalertDir = '/detection-rules/elastalert';
        $files = glob($elastalertDir . '/*.yml');
        $fileCount = count($files);

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

        $this->clearElastalertDirectory();

        $rules = $this->entityManager->getRepository(SigmaRule::class)->findAll();
        foreach ($rules as $rule) {
            $this->entityManager->remove($rule);
        }
        $this->entityManager->flush();

        $io->success("$fileCount ElastAlert rules have been cleared successfully.");
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
