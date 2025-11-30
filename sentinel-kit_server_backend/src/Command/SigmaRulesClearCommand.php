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

#[AsCommand(
    name: 'app:sigma:clear',
    description: 'Clear all Sigma rules from the backend application',
)]
class SigmaRulesClearCommand extends Command
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

        $rules = $this->entityManager->getRepository(SigmaRule::class)->findAll();
        $ruleCount = count($rules);

        if ($ruleCount === 0) {
            $io->info('No Sigma rules found to clear.');
            return Command::SUCCESS;
        }

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

        $syncCommand = $this->getApplication()->find('app:elastalert:clear');
        $syncInput = new ArrayInput(['--force' => true]);
        $syncCommand->run($syncInput, $output);
        

        foreach ($rules as $rule) {
            $this->entityManager->remove($rule);
        }

        $this->entityManager->flush();

        $io->success("$ruleCount Sigma rules have been cleared successfully.");
        return Command::SUCCESS;
    }
}
