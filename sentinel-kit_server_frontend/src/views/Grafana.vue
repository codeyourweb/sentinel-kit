<template>
<iframe
    class="w-full h-full border-0 m-0"
    title="Grafana Dashboard"
    :src="grafanaUrl"
></iframe>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const emit = defineEmits(['show-notification']);

const grafanaUrl = ref(import.meta.env.VITE_GRAFANA_URL || 'https://grafana.sentinel-kit.local');

onMounted(async () => {

    console.log('Loading Grafana dashboard:', grafanaUrl.value);
});

</script>