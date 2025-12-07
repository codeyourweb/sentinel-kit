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

/**
 * User Controller handles user authentication and profile management.
 * 
 * This controller manages user authentication flow including pre-authentication
 * credential validation, two-factor authentication (2FA) with Google Authenticator,
 * and user profile access. Implements a secure two-step authentication process
 * with JWT token management.
 */
class UserController extends AbstractController
{

    /**
     * Entity manager for database operations.
     */
    private $entityManager;
    
    /**
     * Password hasher for credential validation.
     */
    private $passwordHasher;
    
    /**
     * URL generator for creating authentication URLs.
     */
    private $urlGenerator;
    
    /**
     * Google Authenticator service for 2FA.
     */
    private $googleAuthenticator;
    
    /**
     * JWT token manager for authentication tokens.
     */
    private $jwtManager;

    /**
     * Controller constructor with dependency injection.
     * 
     * @param EntityManagerInterface $entityManager Doctrine entity manager
     * @param UserPasswordHasherInterface $passwordHasher Password hasher service
     * @param UrlGeneratorInterface $urlGenerator URL generator service
     * @param GoogleAuthenticatorInterface $googleAuthenticator 2FA authenticator
     * @param JWTTokenManagerInterface $jwtManager JWT token manager
     */
    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, UrlGeneratorInterface $urlGenerator, GoogleAuthenticatorInterface $googleAuthenticator, JWTTokenManagerInterface $jwtManager)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->urlGenerator = $urlGenerator;
        $this->googleAuthenticator = $googleAuthenticator;
        $this->jwtManager = $jwtManager;
    }

    /**
     * Get current user profile information.
     * 
     * Returns basic profile information for the currently authenticated user.
     * Requires valid JWT authentication.
     * 
     * @param Request $request HTTP request (authentication via JWT header)
     * 
     * @return JsonResponse User profile data or error message
     * 
     * Success response (200):
     * {
     *   "id": number,
     *   "email": string,
     *   "roles": string[]
     * }
     * 
     * Error response (404): {"error": "User not found"}
     */
    #[Route('/api/user/info', name: 'user_info', methods: ['GET'])]
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

    /**
     * First step of two-factor authentication process.
     * 
     * Validates user credentials (email/password) and generates a temporary JWT
     * for the second authentication step. Returns a post-auth URL and optional
     * QR code for setting up 2FA for new users.
     * 
     * @param Request $request HTTP request containing login credentials
     * 
     * @return JsonResponse Authentication URLs or error message
     * 
     * Request body:
     * {
     *   "email": string,     // User email (required)
     *   "password": string   // User password (required)
     * }
     * 
     * Success response (200):
     * {
     *   "postAuthUrl": string,  // URL for completing 2FA
     *   "otp_key": string       // QR code URL for new users (may be empty)
     * }
     * 
     * Error responses:
     * - 400: Missing email or password
     * - 401: Invalid credentials
     */
    #[Route('/api/login_pre_auth', name: 'user_login_pre_auth', methods: ['POST'])]
    public function loginPreAuth(Request $request): JsonResponse
    {
        // Extract credentials from request body
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        // Validate required credentials are provided
        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_BAD_REQUEST);
        }

        // Find user by email and validate password
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        // Manage temporary JWT for 2FA process
        $jwt = $user->getUserJWT();
        if (!$jwt || $jwt->getRemainingTry() === 0 || $jwt->isExpired()) {
            // Remove expired or invalid JWT
            if ($jwt) {
                $this->entityManager->remove($jwt);
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            // Create new temporary JWT for 2FA
            $jwt = new UserJWT();
            $jwt->setUser($user);
            $this->entityManager->persist($jwt);
            $this->entityManager->flush();
        }
        
        // Generate QR code for new users (first login)
        $qrCodeContent = '';
        if($user->getUpdatedOn() == null) {
            $qrCodeContent = $this->urlGenerator->generate('qr_code_totp_generator', ['qrCodeContent' => base64_encode($this->googleAuthenticator->getQRContent($user))], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return new JsonResponse(['postAuthUrl' => $this->urlGenerator->generate('user_post_auth', ['uniqueId' => $jwt->getUniqueId()], UrlGeneratorInterface::ABSOLUTE_URL),
                                 'otp_key' => $qrCodeContent], Response::HTTP_OK
        );
    }

    /**
     * Second step of two-factor authentication process.
     * 
     * Validates the OTP (One-Time Password) from Google Authenticator and completes
     * the authentication process by issuing a final JWT token for API access.
     * 
     * @param Request $request HTTP request containing OTP code
     * @param string $uniqueId Unique ID from the pre-auth step
     * 
     * @return JsonResponse JWT token or error message
     * 
     * Request body:
     * {
     *   "otp": string  // 6-digit OTP from Google Authenticator (required)
     * }
     * 
     * Success response (200):
     * {
     *   "token": string  // JWT token for API authentication
     * }
     * 
     * Error responses:
     * - 400: Invalid request format
     * - 401: Invalid unique ID, expired session, or incorrect OTP
     */
    #[Route('/api/login_post_auth/{uniqueId}', name: 'user_post_auth', methods: ['POST'])]
    public function loginPostAuth(Request $request, string $uniqueId): JsonResponse
    {
        // Extract OTP from request body
        $data = json_decode($request->getContent(), true);
        $otp = $data['otp'] ?? null;

        // Validate unique ID parameter
        if (!$uniqueId) {
            return new JsonResponse(['error' => 'Invalid request'], Response::HTTP_BAD_REQUEST);
        }

        // Find temporary JWT by unique ID
        $jwt = $this->entityManager->getRepository(UserJWT::class)->findOneBy(['uniqueId' => $uniqueId]);
        if (!$jwt) {
            return new JsonResponse(['error' => 'Invalid request'], Response::HTTP_UNAUTHORIZED);
        }

        // Get associated user from JWT
        $user = $jwt->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Invalid request'], Response::HTTP_UNAUTHORIZED);
        }

        // Verify OTP code against Google Authenticator
        $isOtpValid = $this->googleAuthenticator->checkCode($user, $otp);
        if (!$isOtpValid) {
            return new JsonResponse(['error' => 'Invalid request'], Response::HTTP_UNAUTHORIZED);
        }

        // Update user's last authentication timestamp
        $user->setUpdatedOn(new \DateTime());
        $this->entityManager->persist($user);
        
        // Clean up temporary JWT
        $this->entityManager->remove($jwt);
        $this->entityManager->flush();

        // Generate final JWT token for API access
        $jwtToken = $this->jwtManager->create($user);
        return new JsonResponse(['token' => $jwtToken], Response::HTTP_OK);
    }

    /**
     * Get authenticated user profile (alternative endpoint).
     * 
     * Alternative endpoint for retrieving user profile information.
     * Provides the same functionality as getUserProfile but with different route.
     * 
     * @param Request $request HTTP request with JWT authentication
     * 
     * @return JsonResponse User profile data or error message
     * 
     * Success response (200):
     * {
     *   "id": number,
     *   "email": string, 
     *   "roles": string[]
     * }
     * 
     * Error response (404): {"error": "User not found"}
     */
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
