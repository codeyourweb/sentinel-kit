<template>
    <form class="login-form max-w-md mx-auto mt-20 p-6 bg-white rounded-lg shadow-md">
        <img src="/images/sentinel-kit_logo.png" alt="Sentinel Kit Logo" class="mx-auto mt-10 mb-6 w-32" />
        <div class="form-group mb-5">
            <label for="email">Email:</label>
            <input type="email" class="input max-w-sm" id="email" v-model="email" required :class="{ 'is-invalid': isInvalid }" />
        </div>
        <div class="form-group mb-5">
            <label for="password">Password:</label>
            <input type="password" class="input max-w-sm" id="password" v-model="password" required :class="{ 'is-invalid': isInvalid }" />
        </div>
        <button type="submit" class="btn btn-primary mt-4" @click.prevent="handleLogin">Login</button>
    </form>

    <div v-if="isInvalid" class="text-red-600 mt-2">
        Invalid credentials. Please try again.
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
const BASE_URL = import.meta.env.VITE_API_BASE_URL;

const email = ref('');
const password = ref('');
const isInvalid = ref(false);
const emit = defineEmits(['preauth-success', 'showNotification']);

watch([email, password], () => {
    isInvalid.value = false;
});

const handleLogin = () => {
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