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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;

/**
 * Users Renew Password Command updates user passwords securely.
 * 
 * This console command allows administrators to reset or update a user's password
 * by providing a new plaintext password that will be securely hashed before storage.
 * Useful for password recovery or administrative password resets.
 */
#[AsCommand(
    name: 'app:users:renew-password',
    description: 'Renew password for a user in the backend application',
)]
class UsersRenewPasswordCommand extends Command
{
    /**
     * Entity manager for database operations.
     */
    private EntityManagerInterface $entityManager;
    
    /**
     * Password hasher for secure password storage.
     */
    private UserPasswordHasherInterface $passwordHasher;
    
    /**
     * URL generator service (unused in this command).
     */
    private UrlGeneratorInterface $urlGenerator;
    
    /**
     * Google Authenticator service (unused in this command).
     */
    private GoogleAuthenticatorInterface $googleAuthenticator;

    /**
     * Command constructor with dependency injection.
     * 
     * @param EntityManagerInterface $entityManager Doctrine entity manager
     * @param UserPasswordHasherInterface $passwordHasher Password hasher service
     * @param UrlGeneratorInterface $urlGenerator URL generator service
     * @param GoogleAuthenticatorInterface $googleAuthenticator 2FA authenticator service
     */
    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, UrlGeneratorInterface $urlGenerator, GoogleAuthenticatorInterface $googleAuthenticator)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->urlGenerator = $urlGenerator;
        $this->googleAuthenticator = $googleAuthenticator;
    }

    /**
     * Configure command arguments.
     * 
     * Defines the required email and password arguments for password renewal.
     */
    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the user')
            ->addArgument('password', InputArgument::REQUIRED, 'New password for the user');
    }

    /**
     * Execute the password renewal command.
     * 
     * Updates the specified user's password with a new securely hashed password.
     * Validates user existence before performing the password update.
     * 
     * @param InputInterface $input Command line input arguments
     * @param OutputInterface $output Command line output interface
     * 
     * @return int Command exit code (SUCCESS or FAILURE)
     * 
     * Arguments:
     * - email: User's email address (must exist in database)
     * - password: New plaintext password (will be hashed before storage)
     * 
     * Exit codes:
     * - Command::SUCCESS (0): Password updated successfully
     * - Command::FAILURE (1): User not found or database error
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize console style helper and extract arguments
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $plainPassword = $input->getArgument('password');

        $userRepository = $this->entityManager->getRepository(User::class);

        // Find the user by email
        $user = $userRepository->findOneBy(['email'=> $input->getArgument('email')]);
        if (null === $user) {
            $io->error('User not found.');
            return Command::FAILURE;   
        }
        
        // Hash the new password securely
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plainPassword
        );

        $user->setPassword($hashedPassword);

        // Save the updated password to database
        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $io->error('An error occurred while resetting user password: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->newLine();
        $io->success('User password reset successful.');
        $io->newLine();

        return Command::SUCCESS;
    }
}
