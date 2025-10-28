<template>
    <LoginOTPForm v-if="otpUrl" :post-auth-url="otpUrl" @postauth-success="handlePostAuthSuccess" />
    <LoginPasswordForm v-else @preauth-success="handlePreAuthSuccess" />
</template>

<script setup>
import { ref } from 'vue';
import LoginOTPForm from '../components/LoginOTPForm.vue';
import LoginPasswordForm from '../components/LoginPasswordForm.vue';

const otpUrl = ref(null);

const handlePreAuthSuccess = (postAuthUrl) => {
    otpUrl.value = postAuthUrl;
};

const handlePostAuthSuccess = (token) => {
    console.log('Storing JWT auth token and redirecting');
    localStorage.setItem('auth_token', token);

    window.location.href = '/';
};
</script>