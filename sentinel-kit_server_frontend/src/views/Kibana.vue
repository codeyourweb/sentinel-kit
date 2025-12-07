<!--
/**
 * Kibana View - Embedded Kibana Dashboard Integration
 * 
 * This view provides direct integration with Kibana for advanced log analysis
 * and data exploration within the Sentinel-Kit platform.
 * 
 * Features:
 * - Embedded Kibana dashboard in iframe
 * - Automatic authentication with Elasticsearch
 * - Configurable Kibana URL via environment variables
 * - Session management and credential handling
 * 
 * Authentication:
 * - Automatic login using configured credentials
 * - Session cookie management for seamless access
 * - XSRF protection for secure API calls
 * 
 * Use Cases:
 * - Advanced log search and analysis
 * - Custom dashboard creation
 * - Data visualization and exploration
 * - Elasticsearch index management
 * - Historical data analysis and reporting
 */
-->

<template>
<!-- Embedded Kibana Dashboard -->
<iframe
    class="w-full h-full border-0 m-0"
    title="Kibana Dashboard"
    :src="kibanaUrl"
></iframe>
</template>

<script setup>
/**
 * Kibana Dashboard Integration Component
 * 
 * Handles the embedding of Kibana dashboards with automatic
 * authentication and session management for seamless access.
 */
import { ref, onMounted } from 'vue';

// Component events
const emit = defineEmits(['show-notification']);

// Kibana instance URL from environment configuration
const kibanaUrl = ref(import.meta.env.VITE_KIBANA_URL);

/**
 * Component initialization with Kibana authentication
 * 
 * Attempts to authenticate with Kibana using configured
 * credentials and establish a session for dashboard access.
 */
onMounted(async () => {
    try{
        // Attempt automatic login to Kibana
        const response = await fetch(kibanaUrl.value, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'kbn-xsrf': 'true' // Required for Kibana XSRF protection
            },
            body: JSON.stringify({
                username: "elastic",
                password: "sentinelkit_elastic_passwd"
            }),
            credentials: 'include' // Include cookies for session management
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