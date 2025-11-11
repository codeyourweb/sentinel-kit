<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Datasource;

#[AsCommand(
    name: 'app:datasource:delete',
    description: 'Delete a datasource from the backend application',
)]
class DatasourceDeleteCommand extends Command
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'ID or name of the datasource to delete');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dsId = $input->getArgument('id');

        if (is_numeric(intval($dsId)) && intval($dsId) > 0) {
            $datasource = $this->entityManager->getRepository(Datasource::class)->find((int)$dsId);
        } else {
            $datasource = $this->entityManager->getRepository(Datasource::class)->findOneBy(['name' => $dsId]);
        }

        if (!$datasource) {
            $io->error('Datasource not found.');
            return Command::FAILURE;
        }

        try{
            $this->entityManager->remove($datasource);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $io->error('An error occurred while deleting the datasource: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->success('Datasource deleted successfully.');

        return Command::SUCCESS;
    }
}
