<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

#[AsCommand(
    name: 'app:users:delete',
    description: 'Delete a user from the backend application',
)]
class UsersDeleteCommand extends Command
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
            ->addArgument('id', InputArgument::REQUIRED, 'ID of the user to delete');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userId = $input->getArgument('id');

        if (is_numeric(intval($userId)) && intval($userId) > 0) {
            $user = $this->entityManager->getRepository(User::class)->find((int)$userId);
        } else {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userId]);
        }

        if (!$user) {
            $io->error('User not found.');
            return Command::FAILURE;
        }

        try{
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $io->error('An error occurred while deleting the user: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->success('User deleted successfully.');

        return Command::SUCCESS;
    }
}
