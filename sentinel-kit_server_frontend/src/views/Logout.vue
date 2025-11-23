<template>
</template>

<script setup>
    import { useRouter } from 'vue-router';
    
      const emit = defineEmits(['show-notification']);
    
    const router = useRouter();
    localStorage.removeItem('auth_token');
    router.push({ name: 'Login' });
</script>