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
 * Users Create Command creates a new user with 2FA authentication.
 * 
 * This command creates a new user account with email and password,
 * automatically sets up Google Authenticator for two-factor authentication,
 * and provides a QR code URL for mobile app configuration.
 * 
 * The command includes validation to prevent duplicate email addresses
 * and provides comprehensive error handling for database operations.
 */
#[AsCommand(
    name: 'app:users:create',
    description: 'Create a new user in the backend application',
)]
class UsersCreateCommand extends Command
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
     * URL generator for creating QR code URLs.
     */
    private UrlGeneratorInterface $urlGenerator;
    
    /**
     * Google Authenticator service for 2FA setup.
     */
    private GoogleAuthenticatorInterface $googleAuthenticator;

    /**
     * Command constructor with dependency injection.
     * 
     * @param EntityManagerInterface $entityManager Doctrine entity manager
     * @param UserPasswordHasherInterface $passwordHasher Password hashing service
     * @param UrlGeneratorInterface $urlGenerator URL generation service
     * @param GoogleAuthenticatorInterface $googleAuthenticator 2FA service
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
     * Sets up required email and password arguments for user creation.
     */
    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the user')
            ->addArgument('password', InputArgument::REQUIRED, 'Password of the user');
    }

    /**
     * Execute the user creation command.
     * 
     * Creates a new user with the provided email and password,
     * sets up Google Authenticator for 2FA, and provides setup instructions.
     * 
     * @param InputInterface $input Command line input arguments
     * @param OutputInterface $output Command line output interface
     * 
     * @return int Command exit code (SUCCESS or FAILURE)
     * 
     * Arguments:
     * - email: User's email address (must be unique)
     * - password: User's plain text password (will be hashed)
     * 
     * Process:
     * 1. Validate email uniqueness
     * 2. Create User entity with hashed password
     * 3. Generate Google Authenticator secret
     * 4. Persist user to database
     * 5. Generate QR code URL for 2FA setup
     * 6. Display setup information
     * 
     * Exit codes:
     * - Command::SUCCESS (0): User created successfully
     * - Command::FAILURE (1): Email already exists or database error
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize console style helper and get arguments
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $plainPassword = $input->getArgument('password');

        // Create new user entity
        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_USER']);

        // Hash the password securely
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plainPassword
        );
        $user->setPassword($hashedPassword);
     
        // Generate Google Authenticator secret for 2FA
        $secret = $this->googleAuthenticator->generateSecret();
        $user->setGoogleAuthenticatorSecret(googleAuthenticatorSecret: $secret);

        // Check for existing user with same email
        try{
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $io->error('A user with this email already exists.');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error('An error occurred while checking for existing user: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Persist new user to database
        try{
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $io->error('An error occurred while creating the user: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Generate QR code content for Google Authenticator setup
        $qrCodeContent = $this->googleAuthenticator->getQRContent($user);
        
        // Display success message and setup information
        $io->newLine();
        $io->success('User successfully created');
        $io->writeln('==============================');
        $io->writeln(sprintf('email: %s', $email));
        $io->writeln(sprintf('OTP authenticator URL: %s', $this->urlGenerator->generate('qr_code_totp_generator', ['qrCodeContent' => base64_encode($qrCodeContent)], UrlGeneratorInterface::ABSOLUTE_URL)));
        $io->writeln('==============================');
        $io->newLine();

        return Command::SUCCESS;
    }
}
