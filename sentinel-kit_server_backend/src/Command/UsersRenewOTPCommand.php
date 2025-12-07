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
 * Users Renew OTP Command regenerates Google Authenticator secrets for users.
 * 
 * This console command allows administrators to reset a user's Google Authenticator
 * configuration by generating a new secret and providing a fresh QR code for setup.
 * Useful when users lose access to their authenticator app or need to reconfigure 2FA.
 */
#[AsCommand(
    name: 'app:users:renew-otp',
    description: 'Renew OTP for a user in the backend application',
)]
class UsersRenewOTPCommand extends Command
{
    /**
     * Entity manager for database operations.
     */
    private EntityManagerInterface $entityManager;
    
    /**
     * Password hasher service (unused in this command but available).
     */
    private UserPasswordHasherInterface $passwordHasher;
    
    /**
     * URL generator for creating QR code URLs.
     */
    private UrlGeneratorInterface $urlGenerator;
    
    /**
     * Google Authenticator service for OTP management.
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
     * Defines the required email argument for user identification.
     */
    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the user');
    }

    /**
     * Execute the OTP renewal command.
     * 
     * Generates a new Google Authenticator secret for the specified user
     * and provides a QR code URL for reconfiguring their authenticator app.
     * 
     * @param InputInterface $input Command line input arguments
     * @param OutputInterface $output Command line output interface
     * 
     * @return int Command exit code (SUCCESS or FAILURE)
     * 
     * Arguments:
     * - email: User's email address (must exist in database)
     * 
     * Output:
     * - Success: New QR code URL for 2FA reconfiguration
     * - Failure: Error message if user not found or database error
     * 
     * Exit codes:
     * - Command::SUCCESS (0): OTP renewed successfully
     * - Command::FAILURE (1): User not found or database error
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize console style helper
        $io = new SymfonyStyle($input, $output);

        $userRepository = $this->entityManager->getRepository(User::class);

        // Find the user by email
        $user = $userRepository->findOneBy(['email'=> $input->getArgument('email')]);
        if (null === $user) {
            $io->error('User not found.');
            return Command::FAILURE;   
        }
        
        // Generate new Google Authenticator secret
        $secret = $this->googleAuthenticator->generateSecret();
        $user->setGoogleAuthenticatorSecret(googleAuthenticatorSecret: $secret);
        $user->setUpdatedOn(null);  // Reset update timestamp to force new setup

        // Save the new secret to database
        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $io->error('An error occurred while resetting user OTP: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Generate new QR code content and display setup information
        $qrCodeContent = $this->googleAuthenticator->getQRContent($user);
        $io->newLine();
        $io->success('User OTP reset successful.');
        $io->writeln('==============================');
        $io->writeln(sprintf('email: %s', $user->getEmail()));
        $io->writeln(sprintf('OTP authenticator URL: %s', $this->urlGenerator->generate('qr_code_totp_generator', ['qrCodeContent' => base64_encode($qrCodeContent)], UrlGeneratorInterface::ABSOLUTE_URL)));
        $io->writeln('==============================');
        $io->newLine();

        return Command::SUCCESS;
    }
}
