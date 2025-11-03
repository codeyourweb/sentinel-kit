<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\UserJWT;

class UserController extends AbstractController
{

    private $entityManager;
    private $passwordHasher;
    private $urlGenerator;
    private $googleAuthenticator;
    private $jwtManager;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, UrlGeneratorInterface $urlGenerator, GoogleAuthenticatorInterface $googleAuthenticator, JWTTokenManagerInterface $jwtManager)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->urlGenerator = $urlGenerator;
        $this->googleAuthenticator = $googleAuthenticator;
        $this->jwtManager = $jwtManager;
    }

    #[Route('/api/user/profile', name: 'user_profile', methods: ['GET'])]
    public function getUserProfile(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/api/login_pre_auth', name: 'user_login_pre_auth', methods: ['POST'])]
    public function loginPreAuth(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $jwt = $user->getUserJWT();
        if (!$jwt || $jwt->getRemainingTry() === 0 || $jwt->isExpired()) {
            if ($jwt) {
                $this->entityManager->remove($jwt);
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $jwt = new UserJWT();
            $jwt->setUser($user);
            $this->entityManager->persist($jwt);
            $this->entityManager->flush();
        }
        
        $qrCodeContent = '';
        if($user->getUpdatedOn() == null) {
            $qrCodeContent = $this->urlGenerator->generate('qr_code_totp_generator', ['qrCodeContent' => base64_encode($this->googleAuthenticator->getQRContent($user))], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return new JsonResponse(['postAuthUrl' => $this->urlGenerator->generate('user_post_auth', ['uniqueId' => $jwt->getUniqueId()], UrlGeneratorInterface::ABSOLUTE_URL),
                                 'otp_key' => $qrCodeContent], Response::HTTP_OK
        );
    }

    #[Route('/api/login_post_auth/{uniqueId}', name: 'user_post_auth', methods: ['POST'])]
    public function loginPostAuth(Request $request, string $uniqueId): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $otp = $data['otp'] ?? null;

        if (!$uniqueId) {
            return new JsonResponse(['error' => 'Invalid request'], Response::HTTP_BAD_REQUEST);
        }

        $jwt = $this->entityManager->getRepository(UserJWT::class)->findOneBy(['uniqueId' => $uniqueId]);
        if (!$jwt) {
            return new JsonResponse(['error' => 'Invalid request'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $jwt->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Invalid request'], Response::HTTP_UNAUTHORIZED);
        }

        $isOtpValid = $this->googleAuthenticator->checkCode($user, $otp);
        if (!$isOtpValid) {
            return new JsonResponse(['error' => 'Invalid request'], Response::HTTP_UNAUTHORIZED);
        }

        $user->setUpdatedOn(new \DateTime());
        $this->entityManager->persist($user);
        $this->entityManager->remove($jwt);
        $this->entityManager->flush();

        $jwtToken = $this->jwtManager->create($user);
        return new JsonResponse(['token' => $jwtToken], Response::HTTP_OK);
    }

    #[Route('/api/user/profile', name: 'user_profile', methods: ['GET'])]
    public function userProfile(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ], Response::HTTP_OK);
    }
}
