<template>
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
                <button 
                    @click.stop="toggleLatestDiff" 
                    class="text-xs font-medium px-3 py-1 rounded transition shadow-sm"
                    :class="isLatestDiffToggled ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200'"
                >
                    <span v-if="isLatestDiffToggled">
                        Hide diff vs previous
                    </span>
                    <span v-else>
                        Show diff vs previous
                    </span>
                </button>
            </div>

            <p v-if="isCurrentVersion(version.id) && (isDiffMode || (isLatestVersion(version.id) && isLatestDiffToggled))" class="text-xs text-red-500 mt-1">
              Compared to previous version
            </p>
          </div>
        </li>
      </ul>
    </div>
    
    <div class="rule-editing w-3/4 p-4">
    <h3 class="text-left text-lg font-semibold mb-4 text-gray-700">{{details?.title }}</h3>
      <div class="monaco-wrapper h-full border border-gray-300 rounded-lg overflow-hidden">
        
        <DiffEditor 
          v-if="isDiffMode || (isLatestVersion(currentVersionId) && isLatestDiffToggled)"
          :original="isLatestDiffToggled ? previousOfLatestContent : previousVersionContent"  
          :modified="currentSelectedContent"   
          language="yaml" 
          theme="vs-light" 
          :options="diffOptions"
          class="monaco-container"
        />

        <VueMonacoEditor 
          v-else
          v-model:value="code" 
          language="yaml" 
          theme="vs-light" 
          :options="editorOptions"
          class="monaco-container"
        />
        
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { DiffEditor, VueMonacoEditor } from '@guolao/vue-monaco-editor'; 
import { useRoute } from 'vue-router';

const BASE_URL = import.meta.env.VITE_API_BASE_URL;
const route = useRoute();

const code = ref("Chargement des données de la règle...");
const details = ref(null);
const currentVersionId = ref(null);

const currentSelectedContent = ref(''); 
const previousVersionContent = ref(''); 
const isDiffMode = ref(false); 
const isLatestDiffToggled = ref(false); 

const editorOptions = computed(() => ({
  automaticLayout: true,
  minimap: { enabled: true },
  readOnly: false,
  tabSize: 2,
  scrollBeyondLastLine: false, 
  wordWrap: 'on',
}));

const diffOptions = computed(() => ({
  automaticLayout: true,
  minimap: { enabled: true },
  readOnly: true, 
  tabSize: 2,
  scrollBeyondLastLine: false, 
  wordWrap: 'on',
  renderSideBySide: true, 
}));


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
    console.error('Erreur: ID de la règle non trouvé.');
    code.value = "Erreur de chargement: ID manquant.";
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
        throw new Error(`Erreur HTTP! Status: ${response.status}`);
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
             code.value = "Aucune version de règle trouvée.";
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

.monaco-container {
  height: 100%;
  width: 100%;
}
</style>