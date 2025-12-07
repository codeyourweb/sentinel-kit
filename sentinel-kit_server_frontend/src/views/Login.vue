<!--
/**
 * Login View - Authentication Flow Management
 * 
 * This view orchestrates the two-step authentication process for Sentinel-Kit.
 * It handles both password authentication and two-factor authentication (2FA)
 * using Google Authenticator TOTP codes.
 * 
 * Features:
 * - Two-step authentication flow
 * - Password-based first authentication step
 * - Google Authenticator TOTP second authentication step
 * - Automatic redirection after successful authentication
 * - JWT token storage for session management
 * 
 * Authentication Flow:
 * 1. User enters email and password (LoginPasswordForm)
 * 2. Backend validates credentials and returns OTP challenge
 * 3. User enters TOTP code from authenticator app (LoginOTPForm)
 * 4. Backend validates TOTP and returns JWT token
 * 5. Token is stored and user redirected to dashboard
 */
-->

<template>
    <!-- Two-Factor Authentication Form -->
    <LoginOTPForm v-if="otpUrl" :post-auth-url="otpUrl" :otp-key="otpKey" @postauth-success="handlePostAuthSuccess" />
    
    <!-- Password Authentication Form -->
    <LoginPasswordForm v-else @preauth-success="handlePreAuthSuccess" />
</template>

<script setup>
/**
 * Login View Component
 * 
 * Manages the authentication state and flow between password
 * authentication and two-factor authentication steps.
 */
import { ref } from 'vue';
import LoginOTPForm from '../components/LoginOTPForm.vue';
import LoginPasswordForm from '../components/LoginPasswordForm.vue';

// Component emits
const emit = defineEmits(['show-notification']);

// Authentication state
const otpUrl = ref(null);
const otpKey = ref(null);

/**
 * Handle successful password authentication
 * Receives OTP challenge data and transitions to 2FA step
 * 
 * @param {Object} responseData - Response from password authentication
 * @param {string} responseData.postAuthUrl - URL for OTP verification
 * @param {string} responseData.otp_key - Key for OTP session
 */
const handlePreAuthSuccess = (responseData) => {
    otpUrl.value = responseData.postAuthUrl;
    otpKey.value = responseData.otp_key;
};

/**
 * Handle successful two-factor authentication
 * Stores JWT token and redirects to dashboard
 * 
 * @param {string} token - JWT authentication token
 */
const handlePostAuthSuccess = (token) => {
    console.log('Storing JWT auth token and redirecting');
    localStorage.setItem('auth_token', token);

    // Redirect to dashboard
    window.location.href = '/';
};
</script>