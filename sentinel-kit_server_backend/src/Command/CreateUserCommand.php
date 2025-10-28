<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;

#[AsCommand(
    name: 'app:users:create',
    description: 'Create a new user in the backend application',
)]
class CreateUserCommand extends Command
{

    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private UrlGeneratorInterface $urlGenerator;
    private GoogleAuthenticatorInterface $googleAuthenticator;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, UrlGeneratorInterface $urlGenerator, GoogleAuthenticatorInterface $googleAuthenticator)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->urlGenerator = $urlGenerator;
        $this->googleAuthenticator = $googleAuthenticator;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the user')
            ->addArgument('password', InputArgument::REQUIRED, 'Password of the user')        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $plainPassword = $input->getArgument('password');

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_USER']);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plainPassword
        );
        $user->setPassword($hashedPassword);
     
        $secret = $this->googleAuthenticator->generateSecret();
        $user->setGoogleAuthenticatorSecret(googleAuthenticatorSecret: $secret);

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

        try{
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $io->error('An error occurred while creating the user: ' . $e->getMessage());
            return Command::FAILURE;
        }


        $qrCodeContent = $this->googleAuthenticator->getQRContent($user);
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
