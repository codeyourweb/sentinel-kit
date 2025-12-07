<!--
/**
 * Login Password Form Component - First Step Authentication
 * 
 * This component handles the first step of the two-factor authentication process,
 * collecting and validating user email and password credentials.
 * 
 * Features:
 * - Email and password input validation
 * - Real-time error feedback and visual indicators
 * - Integration with backend pre-authentication endpoint
 * - Responsive form design with Sentinel-Kit branding
 * - Automatic error state reset on input changes
 * 
 * Authentication Flow:
 * 1. User enters email and password
 * 2. Form submits credentials to backend pre-auth endpoint
 * 3. Backend validates credentials and returns OTP challenge
 * 4. Component emits success event with OTP session data
 * 5. Parent view transitions to OTP authentication step
 * 
 * Error Handling:
 * - Invalid credentials display user-friendly error messages
 * - Network errors are logged for debugging
 * - Form inputs show visual error states
 * - Error state automatically clears on new input
 */
-->

<template>
    <!-- Password Authentication Form -->
    <form class="login-form max-w-md mx-auto mt-20 p-6 bg-white rounded-lg shadow-md">
        <!-- Sentinel-Kit Logo -->
        <img src="/images/sentinel-kit_logo.png" alt="Sentinel Kit Logo" class="mx-auto mt-10 mb-6 w-32" />
        
        <!-- Email Input Field -->
        <div class="form-group mb-5">
            <label for="email">Email:</label>
            <input type="email" class="input max-w-sm" id="email" v-model="email" required :class="{ 'is-invalid': isInvalid }" />
        </div>
        
        <!-- Password Input Field -->
        <div class="form-group mb-5">
            <label for="password">Password:</label>
            <input type="password" class="input max-w-sm" id="password" v-model="password" required :class="{ 'is-invalid': isInvalid }" />
        </div>
        
        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary mt-4" @click.prevent="handleLogin">Login</button>
    </form>

    <!-- Error Message Display -->
    <div v-if="isInvalid" class="text-red-600 mt-2">
        Invalid credentials. Please try again.
    </div>
</template>

<script setup>
/**
 * Login Password Form Component Logic
 * 
 * Manages the first step of authentication with email/password validation
 * and integration with the backend pre-authentication API.
 */
import { ref, watch } from 'vue';

// Backend API base URL
const BASE_URL = import.meta.env.VITE_API_BASE_URL;

// Reactive form data
const email = ref('');
const password = ref('');
const isInvalid = ref(false);

// Component events
const emit = defineEmits(['preauth-success', 'showNotification']);

/**
 * Watch for input changes to reset error state
 * 
 * Automatically clears the invalid state when user starts
 * typing new credentials, providing immediate feedback.
 */
watch([email, password], () => {
    isInvalid.value = false;
});

/**
 * Handle login form submission
 * 
 * Submits email and password to the backend pre-authentication endpoint
 * and handles the response for the next step of 2FA authentication.
 * 
 * Process:
 * 1. Send POST request to /login_pre_auth endpoint
 * 2. Handle various response status codes
 * 3. On success: emit preauth-success with OTP session data
 * 4. On error: display validation error and set invalid state
 * 5. Log network errors for debugging
 */
const handleLogin = () => {
    // Submit credentials to pre-authentication endpoint
    fetch(`${BASE_URL}/login_pre_auth`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            email: email.value,
            password: password.value
        })
    })
    .then(response => {
        if (!response.ok && response.status !== 400 && response.status !== 401) {
            console.error('Login request failed with status ' + response.status + ': ' + response.statusText);
        }

        if (response.status === 400 || response.status === 401) {
            response.json().then(data => {
                console.error('Invalid credentials');
                isInvalid.value = true;
            });
        }

        if (response.status === 200){
            response.json().then(data => {
                if(data.postAuthUrl){
                    console.log('Pre-auth successful');
                    emit('preauth-success', {postAuthUrl: data.postAuthUrl, otp_key: data.otp_key});
                }
            });
        }
    });
};
</script>