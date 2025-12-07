<template>
  <div v-if="showSSLWarning" class="fixed top-0 left-0 right-0 z-50 bg-red-300 border-b border-red-500 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 py-3">
      <div class="flex items-start space-x-3">
        <div class="flex-shrink-0">
          <span class="icon-[material-symbols--security-rounded] w-6 h-6"></span>
        </div>
        
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium">
              Network error detected with some services - expand this panel for details
            </h3>
            <a
              @click="toggleDetails" 
              class="text-black flex items-center space-x-1"
            >
              <span class="text-xs">{{ showDetails ? 'Hide' : 'Details' }}</span>
              <span 
                class="w-4 h-4 transition-transform duration-200"
                :class="[
                  showDetails ? 'icon-[material-symbols--keyboard-arrow-up]' : 'icon-[material-symbols--keyboard-arrow-down]'
                ]"
              ></span>
          </a>
          </div>
          
          <div v-if="showDetails" class="mt-3 space-y-2">
            <div 
              v-for="issue in sslIssues" 
              :key="issue.service"
              class="bg-white rounded-lg p-3 border border-yellow-200"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <h4 class="text-sm font-medium text-gray-900">
                    {{ issue.name }}
                  </h4>
                  <p class="text-xs text-gray-600 mt-1">
                    {{ issue.description }}
                  </p>
                  <p class="text-xs text-red-600 mt-1" v-if="issue.error">
                    {{ issue.error }}
                  </p>
                </div>
                
                <div class="flex items-center space-x-2 ml-4">
                  <span 
                    :class="getStatusClass(issue.status)"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                  >
                    {{ getStatusText(issue.status) }}
                  </span>
                  
                  <a 
                    v-if="issue.status === 'ssl_error'"
                    @click="openApprovalTab(issue.url)"
                    class="btn btn-primary text-xs px-3 py-1 rounded-md transition-colors duration-150"
                  >
                    Approve TLS
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div v-if="showSSLWarning" :class="{ 'h-16': !showDetails, 'h-auto pb-4': showDetails }" class="transition-all duration-300"></div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { checkAllServices } from '../services/sslChecker.js';

const showSSLWarning = ref(false);
const showDetails = ref(false);
const sslIssues = ref([]);
const isChecking = ref(false);
const isDismissed = ref(false);
const lastCheck = ref(null);

let checkInterval = null;

document.addEventListener('visibilitychange', () => {
  if (!document.hidden && !isDismissed.value) {
    checkServices();
  }
});

onMounted(() => {
  checkServices();
  
  checkInterval = setInterval(checkServices, 10000);
});

onUnmounted(() => {
  if (checkInterval) {
    clearInterval(checkInterval);
  }
});

async function checkServices() {
  if (isDismissed.value) return;
  
  isChecking.value = true;
  lastCheck.value = new Date().toLocaleTimeString();
  
  try {
    const results = await checkAllServices();
    
    const issues = results.filter(result => 
      result.status === 'ssl_error' || 
      result.status === 'error' ||
      result.status === 'connection_error' ||
      result.status === 'service_unavailable' ||
      result.status === 'timeout_error' ||
      result.status === 'server_error' ||
      result.status === 'client_error' ||
      result.status === 'not_found' ||
      result.status === 'forbidden' ||
      result.status === 'unauthorized' ||
      result.status === 'backend_error'
    );
    
    sslIssues.value = issues;
    showSSLWarning.value = issues.length > 0;
    
    if (issues.length === 0 && showSSLWarning.value) {
      showSSLWarning.value = false;
      showDetails.value = false;
    }
  } catch (error) {
    console.error('Error during service verification:', error);
  } finally {
    isChecking.value = false;
  }
}

function toggleDetails() {
  showDetails.value = !showDetails.value;
}

function openApprovalTab(url) {
  window.open(url, '_blank', 'noopener,noreferrer');
  
  setTimeout(() => {
    if (!isDismissed.value) {
      checkServices();
    }
  }, 3000);
}

function openAllApprovalTabs() {
  sslIssues.value.forEach(issue => {
    if (issue.status === 'ssl_error') {
      window.open(issue.url, '_blank', 'noopener,noreferrer');
    }
  });
  
  setTimeout(() => {
    if (!isDismissed.value) {
      checkServices();
    }
  }, 5000);
}

function recheckServices() {
  checkServices();
}

function dismissWarning() {
  isDismissed.value = true;
  showSSLWarning.value = false;
  showDetails.value = false;
  
  setTimeout(() => {
    isDismissed.value = false;
  }, 5 * 60 * 1000);
}

function getStatusClass(status) {
  switch (status) {
    case 'ok':
      return 'bg-green-100 text-green-800';
    case 'redirect':
      return 'bg-blue-100 text-blue-800';
    case 'ssl_error':
      return 'bg-red-100 text-red-800';
    case 'connection_error':
    case 'service_unavailable':
    case 'timeout_error':
    case 'server_error':
    case 'backend_error':
      return 'bg-red-100 text-red-800';
    case 'client_error':
    case 'not_found':
    case 'forbidden':
    case 'unauthorized':
      return 'bg-orange-100 text-orange-800';
    case 'error':
      return 'bg-yellow-100 text-yellow-800';
    case 'cors_warning':
      return 'bg-purple-100 text-purple-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
}

function getStatusText(status) {
  switch (status) {
    case 'ok':
      return '✓ OK';
    case 'redirect':
      return '↗ Redirect';
    case 'ssl_error':
      return '⚠ TLS';
    case 'connection_error':
      return '✗ Connection';
    case 'service_unavailable':
      return '✗ Unavailable';
    case 'timeout_error':
      return '⏱ Timeout';
    case 'server_error':
      return '✗ Server';
    case 'client_error':
      return '⚠ Client';
    case 'not_found':
      return '? Not Found';
    case 'forbidden':
      return '🔒 Forbidden';
    case 'unauthorized':
      return '🔑 Auth Required';
    case 'backend_error':
      return '✗ Backend';
    case 'cors_warning':
      return '⚠ CORS';
    case 'error':
      return '✗ Error';
    default:
      return '? Unknown';
  }
}
</script>

<style scoped>
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}
</style>