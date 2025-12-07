<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

/**
 * Users List Command displays all users in the system.
 * 
 * This command retrieves and displays all user accounts from the database
 * in a simple format showing user ID and email address. It provides
 * a quick overview of all registered users for administrative purposes.
 * 
 * The output is formatted for easy readability and can be used for
 * user management and system monitoring tasks.
 */
#[AsCommand(
    name: 'app:users:list',
    description: 'List all users in the backend application',
)]
class UsersListCommand extends Command
{
    /**
     * Entity manager for database operations.
     */
    private EntityManagerInterface $entityManager;

    /**
     * Command constructor with dependency injection.
     * 
     * @param EntityManagerInterface $entityManager Doctrine entity manager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    /**
     * Configure command options.
     * 
     * No additional configuration is required for this command.
     */
    protected function configure(): void
    {
        // No additional configuration required
    }

    /**
     * Execute the user listing command.
     * 
     * Retrieves all users from the database and displays their
     * ID and email address in a formatted list.
     * 
     * @param InputInterface $input Command line input arguments
     * @param OutputInterface $output Command line output interface
     * 
     * @return int Command exit code (SUCCESS)
     * 
     * Output Format:
     * - Each user displayed as: "User ID: [id] | Email: [email]"
     * - Users listed in database order
     * - No pagination or filtering applied
     * 
     * Process:
     * 1. Query database for all user entities
     * 2. Iterate through users and display formatted information
     * 3. Return success status
     * 
     * Exit codes:
     * - Command::SUCCESS (0): List displayed successfully
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize console style helper
        $io = new SymfonyStyle($input, $output);

        // Retrieve all users from database
        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        // Display each user in formatted output
        foreach ($users as $user) {
            $io->writeln('User ID: ' . $user->getId() . ' | Email: ' . $user->getEmail());
        }      
      
        return Command::SUCCESS;
    }
}
