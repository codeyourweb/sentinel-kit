<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Entity\Datasource;


#[AsCommand(
    name: 'app:datasource:create',
    description: 'Create a new ingest datasource in the backend application',
)]
class DatasourceCreateCommand extends Command
{
    private $entityManager;
    private $urlGenerator;

    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the datasource')
            ->addArgument('index', InputArgument::REQUIRED, 'Target index in Elasticsearch')
            ->addArgument('validFrom', InputArgument::OPTIONAL, '(optional) Valid from (YYYY-MM-DD)')
            ->addArgument('validTo', InputArgument::OPTIONAL, '(optional) Valid to (YYYY-MM-DD)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $index = $input->getArgument('index');
        $validFrom = $input->getArgument('validFrom');
        $validTo = $input->getArgument('validTo');

        $validFromDate = null;
        $validToDate = null;

        if ($validFrom) {
            try{
                $validFromDate = new \DateTime($validFrom);
            }catch (\Exception $e){
                $io->error('Invalid date format for validFrom. Please use YYYY-MM-DD.');
                return Command::FAILURE;
            }
        }

        if ($validTo) {
            try {
                $validToDate = new \DateTime($validTo);
            }catch (\Exception $e){
                $io->error('Invalid date format for validTo. Please use YYYY-MM-DD.');
                return Command::FAILURE;
            }
        }

        $existingDatasource = $this->entityManager->getRepository(Datasource::class)->findOneBy(['name' => $name]);

        if ($existingDatasource) {
            $io->error(sprintf('Datasource "%s" already exists.', $name));
            return Command::FAILURE;
        }

        $datasource = new Datasource();
        $output->writeln(sprintf('%s - %s', $name, $index));
        $datasource->setName($name);
        $datasource->setTargetIndex($index);
        $datasource->setValidFrom($validFromDate);
        $datasource->setValidTo($validToDate);

        try {
            $this->entityManager->persist($datasource);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $io->error('An error occurred while creating the datasource: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->success(sprintf('Datasource "%s" created successfully', $name));
        $io->writeln(sprintf('Valid from %s', $validFrom ? $validFromDate->format('Y-m-d') : 'N/A'));
        $io->writeln(sprintf('Valid to %s', $validTo ? $validToDate->format('Y-m-d') : 'N/A'));
        $io->writeln(sprintf('Ingest key (header X-Ingest-Key): %s', $datasource->getIngestKey()));
        $io->writeln(sprintf('Forwarder URL: %s', $this->urlGenerator->generate('app_ingest_json', [], UrlGeneratorInterface::ABSOLUTE_URL)));

        return Command::SUCCESS;
    }
}