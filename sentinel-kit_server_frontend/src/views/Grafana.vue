<!--
/**
 * Grafana View - Embedded Grafana Dashboard Integration
 * 
 * This view provides seamless integration with Grafana dashboards for
 * advanced metrics visualization and monitoring within Sentinel-Kit.
 * 
 * Features:
 * - Embedded Grafana dashboard in iframe
 * - Configurable Grafana URL via environment variables
 * - Full-screen dashboard experience
 * - Seamless integration with Sentinel-Kit navigation
 * 
 * Configuration:
 * - VITE_GRAFANA_URL: Grafana instance URL
 * - Default fallback to local Grafana instance
 * - Supports both local and remote Grafana deployments
 * 
 * Use Cases:
 * - Infrastructure monitoring dashboards
 * - Security metrics visualization
 * - Performance monitoring and alerting
 * - Custom dashboard creation and sharing
 */
-->

<template>
<!-- Embedded Grafana Dashboard -->
<iframe
    class="w-full h-full border-0 m-0"
    title="Grafana Dashboard"
    :src="grafanaUrl"
></iframe>
</template>

<script setup>
/**
 * Grafana Dashboard Integration Component
 * 
 * Handles the embedding and initialization of Grafana dashboards
 * within the Sentinel-Kit platform interface.
 */
import { ref, onMounted } from 'vue';

// Component events
const emit = defineEmits(['show-notification']);

// Grafana instance URL from environment configuration
const grafanaUrl = ref(import.meta.env.VITE_GRAFANA_URL || 'https://grafana.sentinel-kit.local');

/**
 * Component initialization
 * 
 * Sets up the Grafana dashboard integration and logs
 * the dashboard URL for debugging purposes.
 */
onMounted(async () => {
    console.log('Loading Grafana dashboard:', grafanaUrl.value);
});

</script>