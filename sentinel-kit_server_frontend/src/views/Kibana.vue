<template>
<iframe
    class="w-full h-full border-0 m-0"
    title="Kibana Dashboard"
    :src="kibanaUrl"
></iframe>
</template>

<script setup>
import { ref, onMounted } from 'vue';
const kibanaUrl = ref('about:blank');
onMounted(async () => {

try{
    const response = await fetch('https://kibana.sentinel-kit.local/internal/security/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'kbn-xsrf': 'true'
        },
        body: JSON.stringify({
            username: "elastic",
            password: "sentinelkit_elastic_passwd"
        }),
        credentials: 'include'
    });

    if (!response.ok) {
        console.error('Authentication failed:', response.status);
    }
} catch (error) {
    console.error('Error during authentication:', error);
}

kibanaUrl.value = 'https://kibana.sentinel-kit.local/app/dashboards';
});

</script>