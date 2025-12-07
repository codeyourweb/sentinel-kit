<?php

namespace App\Controller;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\ValidationException;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleAuthenticatorTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * QR Code Controller generates QR codes for two-factor authentication setup.
 * 
 * This controller handles the generation of QR codes for various 2FA methods
 * including Google Authenticator and TOTP (Time-based One-Time Password).
 * QR codes are generated as PNG images that users can scan with their
 * authenticator apps to configure 2FA.
 */
class QrCodeController extends AbstractController 
{
    /**
     * Generate QR code for Google Authenticator setup.
     * 
     * Creates a QR code containing the configuration data needed to set up
     * Google Authenticator for the current user. Requires the user to be
     * authenticated and to implement the Google Authenticator interface.
     * 
     * @param TokenStorageInterface $tokenStorage Security token storage for user authentication
     * @param GoogleAuthenticatorInterface $googleAuthenticator Google Authenticator service
     * 
     * @return Response PNG image containing the QR code
     * 
     * @throws NotFoundHttpException If user doesn't support Google Authenticator
     */
    #[Route('/members/qr/ga', name: 'qr_code_ga')]
    public function displayGoogleAuthenticatorQrCode(TokenStorageInterface $tokenStorage, GoogleAuthenticatorInterface $googleAuthenticator): Response
    {
        // Get the authenticated user from security token
        $user = $tokenStorage->getToken()->getUser();
        
        // Verify user supports Google Authenticator interface
        if (!($user instanceof GoogleAuthenticatorTwoFactorInterface)) {
            throw new NotFoundHttpException('Cannot display QR code');
        }

        // Generate QR code with Google Authenticator configuration
        return $this->displayQrCode($googleAuthenticator->getQRContent($user));
    }

    /**
     * Generate QR code for TOTP (Time-based One-Time Password) setup.
     * 
     * Creates a QR code containing the configuration data needed to set up
     * TOTP authentication for the current user. Compatible with various
     * authenticator apps that support TOTP standard.
     * 
     * @param TokenStorageInterface $tokenStorage Security token storage for user authentication
     * @param TotpAuthenticatorInterface $totpAuthenticator TOTP authenticator service
     * 
     * @return Response PNG image containing the QR code
     * 
     * @throws NotFoundHttpException If user doesn't support TOTP authentication
     */
    #[Route('/members/qr/totp', name: 'qr_code_totp')]
    public function displayTotpQrCode(TokenStorageInterface $tokenStorage, TotpAuthenticatorInterface $totpAuthenticator): Response
    {
        // Get the authenticated user from security token
        $user = $tokenStorage->getToken()->getUser();
        
        // Verify user supports TOTP authentication interface
        if (!($user instanceof TotpTwoFactorInterface)) {
            throw new NotFoundHttpException('Cannot display QR code');
        }

        // Generate QR code with TOTP configuration
        return $this->displayQrCode($totpAuthenticator->getQRContent($user));
    }

    /**
     * Generate a QR code from base64-encoded content.
     * 
     * Creates a high-quality PNG QR code image from provided base64-encoded content.
     * This is the core QR code generation method used by other controller methods
     * and can be called directly with encoded content.
     * 
     * @param string $qrCodeContent Base64-encoded content for the QR code
     * 
     * @return Response PNG image response with QR code
     * 
     * QR Code specifications:
     * - Format: PNG
     * - Size: 300x300 pixels
     * - Error correction: High level
     * - Encoding: UTF-8
     * - Margin: None
     */
    #[Route('/qrcode/{qrCodeContent}', name: 'qr_code_totp_generator')]
    public function displayQrCode(string $qrCodeContent): Response
    {
        // Build QR code with high-quality settings
        $b = new Builder(
            writer: new PngWriter(),                           // PNG format output
            writerOptions: [],                                 // Default writer options
            data: base64_decode($qrCodeContent),              // Decode base64 content
            encoding: new Encoding('UTF-8'),                  // UTF-8 encoding
            errorCorrectionLevel: ErrorCorrectionLevel::High, // High error correction
            size: 300,                                        // 300x300 pixel size
            margin: 0,                                        // No margin around QR code
            roundBlockSizeMode: RoundBlockSizeMode::Margin    // Margin-based block rounding
        );
        
        // Generate the QR code image
        $result = $b->build();

        // Return PNG response with proper content type
        return new Response($result->getString(), 200, ['Content-Type' => 'image/png']);
    }
}