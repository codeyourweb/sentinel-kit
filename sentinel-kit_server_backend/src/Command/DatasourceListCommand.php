<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Datasource;

#[AsCommand(
    name: 'app:datasource:list',
    description: 'List all data sources in the backend application',
)]
class DatasourceListCommand extends Command
{

    private EntityManagerInterface $entityManager;

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

        $datasources = $this->entityManager->getRepository(Datasource::class)->findAll();
        foreach ($datasources as $datasource) {
            $io->writeln(
                'ID: ' . $datasource->getId() . 
                ' | Name: ' . $datasource->getName() . 
                ' | Index: ' . $datasource->getTargetIndex() .
                ' | Valid From: ' . ($datasource->getValidFrom() ? $datasource->getValidFrom()->format('Y-m-d') : 'N/A') .
                ' | Valid To: ' . ($datasource->getValidTo() ? $datasource->getValidTo()->format('Y-m-d') : 'N/A')
            );
        }

        return Command::SUCCESS;
    }
}
