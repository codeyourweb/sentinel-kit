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

/**
 * Users Delete Command removes a user from the system.
 * 
 * This command deletes a user account by either user ID or email address.
 * It includes proper validation to ensure the user exists before deletion
 * and provides comprehensive error handling for database operations.
 * 
 * The command supports flexible user identification allowing deletion
 * by numeric ID or email address for administrative convenience.
 */
#[AsCommand(
    name: 'app:users:delete',
    description: 'Delete a user from the backend application',
)]
class UsersDeleteCommand extends Command
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
     * Configure command arguments.
     * 
     * Sets up the user ID/email argument for user identification.
     */
    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'ID of the user to delete');
    }

    /**
     * Execute the user deletion command.
     * 
     * Deletes a user account identified by either user ID or email address.
     * Validates user existence before attempting deletion.
     * 
     * @param InputInterface $input Command line input arguments
     * @param OutputInterface $output Command line output interface
     * 
     * @return int Command exit code (SUCCESS or FAILURE)
     * 
     * Arguments:
     * - id: User identifier (numeric ID or email address)
     * 
     * Process:
     * 1. Parse identifier as numeric ID or email address
     * 2. Query database for user by appropriate field
     * 3. Validate user exists
     * 4. Remove user from database
     * 5. Confirm deletion success
     * 
     * User Identification:
     * - Numeric values are treated as user IDs
     * - Non-numeric values are treated as email addresses
     * 
     * Exit codes:
     * - Command::SUCCESS (0): User deleted successfully
     * - Command::FAILURE (1): User not found or database error
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize console style helper and get arguments
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getArgument('id');

        // Determine search method based on identifier format
        if (is_numeric(intval($userId)) && intval($userId) > 0) {
            // Search by numeric user ID
            $user = $this->entityManager->getRepository(User::class)->find((int)$userId);
        } else {
            // Search by email address
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userId]);
        }

        // Validate user exists
        if (!$user) {
            $io->error('User not found.');
            return Command::FAILURE;
        }

        // Remove user from database
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
