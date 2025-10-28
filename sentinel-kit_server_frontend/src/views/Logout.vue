<template>
</template>

<script setup>
    import { useRouter } from 'vue-router';
    const router = useRouter();
    localStorage.removeItem('auth_token');
    router.push({ name: 'Login' });
</script>