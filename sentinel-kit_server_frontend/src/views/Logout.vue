<!--
/**
 * Logout View - Session Termination Handler
 * 
 * This view handles user logout by clearing authentication tokens
 * and redirecting to the login page. It provides a clean session
 * termination process for the Sentinel-Kit platform.
 * 
 * Features:
 * - JWT token removal from localStorage
 * - Automatic redirection to login page
 * - Clean session termination
 * - Immediate logout processing
 * 
 * Security:
 * - Ensures complete token cleanup
 * - Prevents unauthorized access after logout
 * - Secure session management
 */
-->

<template>
<!-- Empty template - logout is handled in script -->
</template>

<script setup>
/**
 * Logout Component
 * 
 * Immediately processes user logout by clearing stored
 * authentication tokens and redirecting to login page.
 */
import { useRouter } from 'vue-router';
    
// Component events
const emit = defineEmits(['show-notification']);

// Vue router instance
const router = useRouter();

// Immediately clear authentication token and redirect
localStorage.removeItem('auth_token');
router.push({ name: 'Login' });
</script>