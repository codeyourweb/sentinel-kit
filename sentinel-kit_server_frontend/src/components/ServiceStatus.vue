<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <div
      v-for="service in services"
      :key="service.service"
      class="bg-white rounded-lg shadow-md border-l-4 p-4 transition-all duration-200 hover:shadow-lg"
      :class="getServiceBorderClass(service.status)"
    >
      <div class="flex items-center justify-between">
        <div class="flex-1">
          <h3 class="text-lg font-semibold text-gray-900 text-justify">
            {{ service.name }}
          </h3>
          <p class="text-sm text-gray-600 mt-1 text-justify">
            {{ service.description }}
          </p>
        </div>
        
        <div class="flex items-center space-x-2">
          <span 
            class="w-3 h-3 rounded-full"
            :class="getServiceIndicatorClass(service.status)"
          ></span>
          <span 
            class="text-sm font-medium"
            :class="getServiceTextClass(service.status)"
          >
            {{ getServiceStatusText(service.status) }}
          </span>
        </div>
      </div>
      
      <div v-if="service.error && service.status !== 'ok'" class="mt-2">
        <p class="text-xs text-red-600 bg-red-50 rounded p-2">
          {{ service.error }}
        </p>
      </div>
      
      <div v-if="service.status === 'ssl_error'" class="mt-3 flex flex-wrap gap-2">
        <a 
          @click="openApprovalTab(service.url)"
          class="btn btn-primary text-xs px-3 py-1 rounded-md transition-colors duration-150 flex items-center space-x-1"
        >
          <span class="icon-[material-symbols--security] w-3 h-3"></span>
          <span>Approve TLS certificate</span>
        </a>
      </div>
      
      <div v-if="service.status === 'down'" class="mt-3 flex flex-wrap gap-2">
        <button 
          @click="refreshServices"
          class="btn btn-secondary text-xs px-3 py-1 rounded-md transition-colors duration-150 flex items-center space-x-1"
        >
          <span class="icon-[material-symbols--refresh] w-3 h-3"></span>
          <span>Retry Connection</span>
        </button>
      </div>
      
      <div v-if="service.status === 'timeout'" class="mt-3 flex flex-wrap gap-2">
        <button 
          @click="refreshServices"
          class="btn btn-warning text-xs px-3 py-1 rounded-md transition-colors duration-150 flex items-center space-x-1"
        >
          <span class="icon-[material-symbols--schedule] w-3 h-3"></span>
          <span>Retry</span>
        </button>
      </div>
      

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { checkAllServices } from '../services/sslChecker.js';

const services = ref([]);
const isLoading = ref(false);
let refreshInterval = null;

const hasSSLErrors = computed(() => {
  return services.value.some(service => service.status === 'ssl_error');
});

onMounted(() => {
  loadServices();
  
  refreshInterval = setInterval(loadServices, 5000);
});

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval);
  }
});

async function loadServices() {
  isLoading.value = true;
  
  try {
    const results = await checkAllServices();
    services.value = results;
  } catch (error) {
    console.error('Error loading services:', error);
  } finally {
    isLoading.value = false;
  }
}

function refreshServices() {
  loadServices();
}

function openApprovalTab(url) {
  window.open(url, '_blank', 'noopener,noreferrer');
}

function openService(url) {
  window.open(url, '_blank', 'noopener,noreferrer');
}

function approveAllCertificates() {
  services.value
    .filter(service => service.status === 'ssl_error')
    .forEach(service => {
      window.open(service.url, '_blank', 'noopener,noreferrer');
    });
}

function getServiceBorderClass(status) {
  switch (status) {
    case 'ok':
      return 'border-l-green-500';
    case 'redirect':
      return 'border-l-blue-500';
    case 'ssl_error':
      return 'border-l-orange-500';
    case 'connection_error':
    case 'service_unavailable':
    case 'timeout_error':
    case 'server_error':
    case 'backend_error':
    case 'down':
      return 'border-l-red-600';
    case 'client_error':
    case 'not_found':
    case 'forbidden':
    case 'unauthorized':
      return 'border-l-orange-500';
    case 'cors_warning':
      return 'border-l-purple-500';
    case 'unreachable':
      return 'border-l-red-400';
    case 'timeout':
      return 'border-l-yellow-500';
    case 'error':
      return 'border-l-red-500';
    default:
      return 'border-l-gray-500';
  }
}

function getServiceIndicatorClass(status) {
  switch (status) {
    case 'ok':
      return 'bg-green-500';
    case 'redirect':
      return 'bg-blue-500';
    case 'ssl_error':
      return 'bg-orange-500';
    case 'connection_error':
    case 'service_unavailable':
    case 'server_error':
    case 'backend_error':
    case 'down':
      return 'bg-red-600 animate-pulse';
    case 'timeout_error':
    case 'timeout':
      return 'bg-yellow-500';
    case 'client_error':
    case 'not_found':
    case 'forbidden':
    case 'unauthorized':
      return 'bg-orange-500';
    case 'cors_warning':
      return 'bg-purple-500';
    case 'unreachable':
      return 'bg-red-400';
    case 'error':
      return 'bg-red-500';
    default:
      return 'bg-gray-500';
  }
}

function getServiceTextClass(status) {
  switch (status) {
    case 'ok':
      return 'text-green-700';
    case 'redirect':
      return 'text-blue-700';
    case 'ssl_error':
      return 'text-orange-700';
    case 'connection_error':
    case 'service_unavailable':
    case 'server_error':
    case 'backend_error':
    case 'down':
      return 'text-red-800 font-semibold';
    case 'timeout_error':
    case 'timeout':
      return 'text-yellow-700';
    case 'client_error':
    case 'not_found':
    case 'forbidden':
    case 'unauthorized':
      return 'text-orange-700';
    case 'cors_warning':
      return 'text-purple-700';
    case 'unreachable':
      return 'text-red-600';
    case 'error':
      return 'text-red-700';
    default:
      return 'text-gray-700';
  }
}

function getServiceStatusText(status) {
  switch (status) {
    case 'ok':
      return 'Operational';
    case 'redirect':
      return 'Redirecting';
    case 'ssl_error':
      return 'TLS Required';
    case 'connection_error':
      return 'Connection Failed';
    case 'service_unavailable':
      return 'Service Unavailable';
    case 'timeout_error':
      return 'Connection Timeout';
    case 'server_error':
      return 'Server Error';
    case 'client_error':
      return 'Client Error';
    case 'not_found':
      return 'Not Found';
    case 'forbidden':
      return 'Access Denied';
    case 'unauthorized':
      return 'Auth Required';
    case 'backend_error':
      return 'Backend Error';
    case 'cors_warning':
      return 'CORS Issue';
    case 'down':
      return 'Service Down';
    case 'unreachable':
      return 'Unreachable';
    case 'timeout':
      return 'Slow Response';
    case 'error':
      return 'Error';
    default:
      return 'Unknown';
  }
}

function formatTime(timestamp) {
  const date = new Date(timestamp);
  return date.toLocaleTimeString('en-US', { 
    hour: '2-digit', 
    minute: '2-digit' 
  });
}
</script>