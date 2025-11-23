<template>
    <LoginOTPForm v-if="otpUrl" :post-auth-url="otpUrl" :otp-key="otpKey" @postauth-success="handlePostAuthSuccess" />
    <LoginPasswordForm v-else @preauth-success="handlePreAuthSuccess" />
</template>

<script setup>
import { ref } from 'vue';
import LoginOTPForm from '../components/LoginOTPForm.vue';
import LoginPasswordForm from '../components/LoginPasswordForm.vue';

const emit = defineEmits(['show-notification']);

const otpUrl = ref(null);
const otpKey = ref(null);

const handlePreAuthSuccess = (responseData) => {
    otpUrl.value = responseData.postAuthUrl;
    otpKey.value = responseData.otp_key;
};

const handlePostAuthSuccess = (token) => {
    console.log('Storing JWT auth token and redirecting');
    localStorage.setItem('auth_token', token);

    window.location.href = '/';
};
</script>