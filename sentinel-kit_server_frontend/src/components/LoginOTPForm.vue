<template>
    <form class="login-form max-w-md mx-auto mt-20 p-6 bg-white rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4">Enter OTP</h2>
        <div class ="mb-4" v-if="otpKey != ''">
            <h2 class="text-lg font-semibold">First time login.</h2>
            <p>Scan the QR code below with your authenticator app and fulfill the 6-digit OTP.</p>
            <div class="flex items-center justify-center">
                <img :src="otpKey" alt="OTP QR Code" class="object-center" />
            </div>
        </div>
        <div class="otp-inputs">
            <input type="text" id="otp0" class="input max-w-sm" :class="{ 'is-invalid': isInvalid }" maxlength="1" v-model="otp[0]" @input="focusNext(0)" @keydown.backspace="focusPrev(0)" />
            <input type="text" id="otp1" class="input max-w-sm" :class="{ 'is-invalid': isInvalid }" maxlength="1" v-model="otp[1]" @input="focusNext(1)" @keydown.backspace="focusPrev(1)" />
            <input type="text" id="otp2" class="input max-w-sm" :class="{ 'is-invalid': isInvalid }" maxlength="1" v-model="otp[2]" @input="focusNext(2)" @keydown.backspace="focusPrev(2)" />
            <input type="text" id="otp3" class="input max-w-sm" :class="{ 'is-invalid': isInvalid }" maxlength="1" v-model="otp[3]" @input="focusNext(3)" @keydown.backspace="focusPrev(3)" />
            <input type="text" id="otp4" class="input max-w-sm" :class="{ 'is-invalid': isInvalid }" maxlength="1" v-model="otp[4]" @input="focusNext(4)" @keydown.backspace="focusPrev(4)" />
            <input type="text" id="otp5" class="input max-w-sm" :class="{ 'is-invalid': isInvalid }" maxlength="1" v-model="otp[5]" @input="handleSubmit" @keydown.backspace="focusPrev(5)" />
        </div>
    </form>
    <div v-if="isInvalid" class="text-red-600 mt-2">
        Invalid OTP. Please try again.
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const isInvalid = ref(false);
const otp = ref(['', '', '', '', '', '']);
const emit = defineEmits(['postauth-success']);
const props = defineProps({
    postAuthUrl: {
        type: String,
        required: true
    },
    otpKey: {
        type: String,
        required: true
    }
});

const focusNext = (index) => {
    if (otp.value[index].length === 1 && index < 5) {
        const nextInput = document.querySelectorAll('.otp-inputs input')[index + 1];
        nextInput.focus();
    }
};

const focusPrev = (index) => {
    if (index > 0) {
        const prevInput = document.querySelectorAll('.otp-inputs input')[index - 1];
        prevInput.focus();
    }
};

const handleSubmit = async () => {
    const otpCode = otp.value.join('');

    try {
        const response = await fetch(props.postAuthUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                otp: otpCode,
                postAuthUrl: props.postAuthUrl
            })
        });
        
        if (response.status === 401) {
            console.log('Invalid OTP');
            isInvalid.value = true;

            otp.value = ['', '', '', '', '', ''];
            const firstInput = document.querySelectorAll('.otp-inputs input')[0];
            firstInput.focus();
            return;
        }

        if (!response.ok) {
            console.error('Login request failed with status ' + response.status + ': ' + response.statusText);
            router.push({ name: 'Login' });
            return;
        }

        if (response.ok) {
            const data = await response.json(); 
            if (data.token) {
                console.log('OTP verified successfully');
                emit('postauth-success', data.token);
            }
        }

    } catch (error) {
        console.error('An error occurred during OTP submission:', error);
    }
};
</script>

<style scoped>
.login-otp-form {
    max-width: 400px;
    margin: auto;
    padding: 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
    text-align: center;
}
.otp-inputs {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
}
.otp-inputs input {
    width: 40px;
    height: 40px;
    font-size: 24px;
    text-align: center;
    border: #333 solid 1px;
    border-radius: 5px;
    margin: 10px;
}
</style>