<!--
/**
 * Rule Edit View - Sigma Rule Editor with Version History
 * 
 * This view provides a comprehensive interface for editing Sigma detection rules
 * with full version history management and real-time code editing capabilities.
 * 
 * Features:
 * - Monaco Editor integration for YAML syntax highlighting
 * - Rule version history with diff visualization
 * - Real-time rule validation and syntax checking
 * - Active/inactive rule state management
 * - Automatic change detection with unsaved changes warnings
 * - Version comparison and diff viewing
 * - Responsive layout with resizable panels
 * 
 * Layout:
 * - Left Panel: Rule version history and navigation
 * - Center Panel: Monaco code editor with YAML content
 * - Top Bar: Rule metadata and action buttons
 * 
 * Data Flow:
 * - Fetches rule data and versions from backend API
 * - Loads rule content into Monaco editor
 * - Tracks changes and enables save functionality
 * - Supports version switching and diff viewing
 */
-->

<template>
  <!-- Main Rule Editor Layout -->
  <div class="flex h-screen-minus-header">
    
    <div class="rule-versioning w-1/4 p-4 border-r border-gray-200 overflow-y-auto">
      <h3 class="text-lg font-semibold mb-4 text-gray-700">Rule history</h3>
      <ul>
        
        <li v-if="!details">
            <p class="text-gray-500">Loading versions...</p>
        </li>
        <li v-else-if="details && details.versions && details.versions.length === 0">
            <p class="text-gray-500">No versions found.</p>
        </li>
        
        <li v-for="version in details?.versions" :key="version.id">
          
          <div 
            class="rule-summary border-2 p-4 mb-3 rounded-lg cursor-pointer transition"
            :class="isCurrentVersion(version.id) ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 hover:bg-gray-50'"
            @click="loadVersionContent(version)"
          >
            <div class="flex justify-between items-center">
              <span class="text-sm">
                {{ version.createdOn }}
              </span>
              <span v-if="isLatestVersion(version.id)" class="text-xs font-medium px-2 py-1 bg-green-200 text-green-800 rounded-full">
                Latest
              </span>
              <span v-else-if="version.id === initialVersionId" class="text-xs font-medium px-2 py-1 bg-yellow-200 text-yellow-800 rounded-full">
                Initial
              </span>
            </div>
            
            <div 
                v-if="isCurrentVersion(version.id) && isLatestVersion(version.id) && hasPreviousVersion" 
                class="mt-2"
            >
                <a 
                    @click.stop="toggleLatestDiff" 
                    class="btn btn-primary text-xs font-medium px-3 py-1 rounded transition shadow-sm"
                >
                    <span v-if="isLatestDiffToggled">
                        Hide diff vs previous
                    </span>
                    <span v-else>
                        Show diff vs previous
                    </span>
                  </a>
            </div>

            <p v-if="isCurrentVersion(version.id) && (isDiffMode || (isLatestVersion(version.id) && isLatestDiffToggled))" class="text-xs text-red-500 mt-1">
              Compared to previous version
            </p>
          </div>
        </li>
      </ul>
    </div>
    
    <div class="rule-editing w-3/4 p-4">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-left text-lg font-semibold text-gray-700">{{details?.title }}</h3>
        
        <div class="flex items-center space-x-4">
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-600">Rule enabled: </span>
            <div v-if="isUpdatingStatus" class="flex items-center">
              <span class="icon-[svg-spinners--ring-resize] w-6 h-6 text-blue-500 animate-spin"></span>
            </div>
            <label v-else-if="details" class="relative inline-flex items-center cursor-pointer">
              <input 
                type="checkbox" 
                class="sr-only peer" 
                v-model="ruleActiveStatus"
                @change="toggleStatus" 
              />
              <div 
                class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"
              ></div>
            </label>
          </div>
          
          <a 
            v-if="!isDiffMode && !isLatestDiffToggled"
            @click="handleSaveClick"
            class="btn px-4 py-2 rounded flex items-center transition-colors duration-200"
            :class="{
              'btn-primary text-white cursor-pointer': hasChanges && !isSaving,
              'bg-gray-300 text-gray-500 cursor-not-allowed pointer-events-none': !hasChanges || isSaving
            }"
          >
            <span v-if="isSaving" class="icon-[svg-spinners--ring-resize] w-4 h-4 mr-2 animate-spin"></span>
            <span class="icon-[material-symbols--save-rounded] bg-white text-white w-4 h-4 mr-2" v-else></span>
            Save changes
          </a>
        </div>
      </div>
      
      <div v-if="activationError" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex">
          <div class="flex-shrink-0">
            <span class="icon-[material-symbols--error] w-5 h-5 text-red-400"></span>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800 text-left"><strong>Rule activation failed</strong></h3>
            <div class="mt-2 text-sm text-red-700">
              <p class="text-justify">{{ activationError }}</p>
            </div>
          </div>
        </div>
      </div>
      
      <div v-if="syntaxError" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">
              Error saving changes:
            </h3>
            <div class="mt-2 text-sm text-red-700">
              <pre class="whitespace-pre-wrap">{{ syntaxError }}</pre>
            </div>
          </div>
        </div>
      </div>
      
      <RuleEditor 
        v-model="code"
        :is-diff-mode="isDiffMode"
        :is-latest-diff-toggled="isLatestDiffToggled"
        :original-content="isLatestDiffToggled ? previousOfLatestContent : previousVersionContent"
        :read-only="isDiffMode || isLatestDiffToggled"
        class="h-full"
      />
      </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, defineEmits } from 'vue';
import { useRoute } from 'vue-router';
import RuleEditor from '../components/RuleEditor.vue';

const BASE_URL = import.meta.env.VITE_API_BASE_URL;
const route = useRoute();
const emit = defineEmits(['show-notification']);

const code = ref("Loading rule data...");
const details = ref(null);
const currentVersionId = ref(null);
const originalContent = ref('');
const hasChanges = ref(false);
const isUpdatingStatus = ref(false);
const isSaving = ref(false);
const syntaxError = ref(null);
const activationError = ref(null);

const currentSelectedContent = ref(''); 
const previousVersionContent = ref(''); 
const isDiffMode = ref(false); 
const isLatestDiffToggled = ref(false); 

const latestVersionId = computed(() => {
    return details.value?.versions?.length > 0 ? details.value.versions[0].id : null;
});

const initialVersionId = computed(() => {
    const versions = details.value?.versions;
    return versions?.length > 0 ? versions[versions.length - 1].id : null;
});

const previousOfLatestContent = computed(() => {
    const versions = details.value?.versions;
    return versions?.length > 1 ? versions[1].content.replace(/\\n/g, '\n') || "No previous content." : '';
});

const hasPreviousVersion = computed(() => {
    return details.value?.versions?.length > 1;
});

const ruleActiveStatus = computed({
    get() {
        return details.value?.active || false;
    },
    set(value) {
        if (details.value) {
            details.value.active = value;
        }
    }
});

watch(code, (newContent) => {
    if (originalContent.value && !isDiffMode.value && !isLatestDiffToggled.value) {
        hasChanges.value = newContent !== originalContent.value;
    } else {
        hasChanges.value = false;
    }
});

const toggleStatus = async () => {
    if (isUpdatingStatus.value || !details.value) return;

    activationError.value = null;
    isUpdatingStatus.value = true;
    const newStatus = ruleActiveStatus.value;
    const previousStatus = !newStatus;
    
    try {
        const response = await fetch(`${BASE_URL}/rules/sigma/${details.value.id}/status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            },
            body: JSON.stringify({ active: newStatus })
        });

        if (response.ok) {
            emit('show-notification', { 
                message: `Rule ${newStatus ? 'activated' : 'deactivated'} successfully!`, 
                type: 'success' 
            });
        } else {
            const errorData = await response.json().catch(() => ({}));
            const errorMessage = errorData.error || errorData.details || 'Failed to update rule status';
            
            if (details.value) {
                details.value.active = previousStatus;
            }
            
            activationError.value = errorMessage;
            
            console.error('API update failed:', errorMessage);
            emit('show-notification', { 
                message: 'Failed to update rule status - see details above', 
                type: 'error' 
            });
        }
    } catch (error) {
        console.error('Network or parsing error:', error);
        if (details.value) {
            details.value.active = previousStatus;
        }
        activationError.value = 'Network error occurred while updating rule status';
        emit('show-notification', { 
            message: 'An error occurred while updating rule status', 
            type: 'error' 
        });
    } finally {
        isUpdatingStatus.value = false;
    }
};

const handleSaveClick = (event) => {
    if (!hasChanges.value || isSaving.value) {
        event.preventDefault();
        return;
    }
    saveChanges();
};

const saveChanges = async () => {
    if (isSaving.value || !hasChanges.value) return;
    
    syntaxError.value = null;
    isSaving.value = true;
    
    try {
        const response = await fetch(`${BASE_URL}/rules/sigma/${details.value.id}/add_version`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            },
            body: JSON.stringify({
                rule_content: code.value
            })
        });
        
        const data = await response.json();
        
        if (data.error && data.error.length > 0) {
            syntaxError.value = data.error;
        } else {
            emit('show-notification', { 
                message: 'New version saved successfully!', 
                type: 'success' 
            });
            fetchRuleData();
            hasChanges.value = false;
        }
    } catch (error) {
        console.error('Error:', error);
        emit('show-notification', { 
            message: 'An error occurred while saving changes: ' + error.message, 
            type: 'error' 
        });
    } finally {
        isSaving.value = false;
    }
};


const isCurrentVersion = (versionId) => {
    return versionId === currentVersionId.value;
};

const isLatestVersion = (versionId) => {
    return versionId === latestVersionId.value;
}

const toggleLatestDiff = () => {
    isLatestDiffToggled.value = !isLatestDiffToggled.value;
};


const loadVersionContent = (version) => {
    const selectedContent = version.content.replace(/\\n/g, '\n') || "No content available.";
    currentSelectedContent.value = selectedContent;
    code.value = selectedContent;
    
    if (version.id === latestVersionId.value) {
        originalContent.value = selectedContent;
        hasChanges.value = false;
    }
    
    currentVersionId.value = version.id;

    const isLatest = version.id === latestVersionId.value;
    const isInitial = version.id === initialVersionId.value;

    if (!isLatest) {
        isLatestDiffToggled.value = false;
    }

    isDiffMode.value = !isLatest && !isInitial; 
  
    if (isDiffMode.value && details.value?.versions) {
        const versions = details.value.versions;
        const selectedIndex = versions.findIndex(v => v.id === version.id); 
        const previousVersion = versions[selectedIndex + 1];

        if (previousVersion) {
            previousVersionContent.value = previousVersion.content.replace(/\\n/g, '\n') || "No content available.";
        } else {
            previousVersionContent.value = "No previous content to compare.";
        }
    } else {
        previousVersionContent.value = '';
    }
};


const fetchRuleData = () => {
    const ruleId = route.params.id; 
    
    if (!ruleId) {
      console.error('Error: Rule ID not found.');
      code.value = "Loading error: Missing ID.";
      return;
    }
    
    fetch(`${BASE_URL}/rules/sigma/${ruleId}/details`, {
      method: 'GET',
      headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      },
  })
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP Error! Status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
          details.value = data;

          if (data && data.versions && data.versions.length > 0) {
              const latestVersion = data.versions[0];
              loadVersionContent(latestVersion);
          } else {
              details.value = { versions: [] };
              code.value = "No rule version found.";
          }
      })
      .catch(error => {
        console.error('Error while retrieving rule\'s data:', error);
        code.value = `Error loading: ${error.message}`;
      });
};

onMounted(fetchRuleData);
</script>

<style scoped>
.h-screen-minus-header {
  height: calc(100vh - 64px); 
}
</style>