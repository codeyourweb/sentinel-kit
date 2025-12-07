<!--
/**
 * Rule Create View - New Sigma Rule Creation Interface
 * 
 * This view provides a user-friendly interface for creating new Sigma detection
 * rules with integrated YAML editing and real-time validation capabilities.
 * 
 * Features:
 * - Monaco Editor integration with YAML syntax highlighting
 * - Pre-loaded rule template with examples and guidelines
 * - Real-time syntax validation with error reporting
 * - Automatic rule structure validation
 * - Save functionality with backend integration
 * - Error handling and user feedback
 * - Guided rule creation with documentation links
 * 
 * Rule Template:
 * - Includes complete Sigma rule structure example
 * - MITRE ATT&CK framework integration examples
 * - Common field patterns and modifiers
 * - Severity levels and detection logic examples
 * - Best practices and documentation references
 * 
 * Validation:
 * - YAML syntax checking
 * - Sigma rule schema validation
 * - Required field verification (title, description)
 * - Error display with detailed feedback
 * 
 * Workflow:
 * 1. User edits rule content in Monaco editor
 * 2. Real-time syntax validation provides feedback
 * 3. Save button triggers backend validation
 * 4. Success redirects to rules list or edit view
 * 5. Errors displayed with specific issue details
 */
-->

<template>
  <!-- Rule Creation Container -->
  <div class="flex flex-col h-screen">
    <div class="flex-shrink-0 p-4 border-b border-gray-200 flex justify-between items-center">
      <h1 class="text-4xl font-extrabold text-gray-900">New sigma rule</h1>
      <a @click="saveRule" class="btn btn-primary px-4 py-2 rounded">
        <span class="icon-[material-symbols--save-rounded] bg-white text-white"></span> Save
      </a>
    </div>
    
    <div v-if="syntaxError" class="flex-shrink-0 mx-4 mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800 text-left">
            <strong>Error saving rule:</strong>
          </h3>
          <div class="mt-2 text-sm text-red-700">
            <pre class="whitespace-pre-wrap">{{ syntaxError }}</pre>
          </div>
        </div>
      </div>
    </div>

    <div class="flex-1 p-4">
      <RuleEditor 
        v-model="newRuleContent"
        :read-only="false"
        class="h-full w-full"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, defineEmits } from 'vue';
import { useRouter } from 'vue-router';
import RuleEditor from '../components/RuleEditor.vue';

const newRuleContent = ref('#THIS IS AN EXAMPLE - You can completly remove it to make your own rule\n#Rule creation guide: https://github.com/SigmaHQ/sigma/wiki/Rule-Creation-Guide\n#-----------------------------------------\n\n\ntitle: a short capitalised title with less than 50 characters  # REQUIRED - will be your rule title in Sentinel-Kit\ndescription: A description of what your rule is meant to detect # REQUIRED - will be you rule description in Sentinel-Kit\nreferences:\n    - A list of all references that can help a reader or analyst understand the meaning of a triggered rule\ntags: # OPTIONAL but useful for rule filtering and detection coverage assessments\n    - attack.execution  # example MITRE ATT&CK category\n    - attack.t1059      # example MITRE ATT&CK technique id\n    - car.2014-04-003   # example CAR id\ndetection:\n    selection:\n        FieldName: \'StringValue\'\n        FieldName: IntegerValue\n        FieldName|modifier: \'Value\'\n    condition: selection\nlevel: alert severity weight # optional - supported values : informational, low, medium, high, critical - default: informational');

const BASE_URL = import.meta.env.VITE_API_BASE_URL;
const router = useRouter();
const emit = defineEmits(['show-notification']);

const syntaxError = ref(null);

function saveRule() {
  syntaxError.value = null;
  
  fetch(`${BASE_URL}/rules/sigma/add_rule`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      rule_content: newRuleContent.value,
    }),
  }).then(response => response.json())
    .then(data => {
      if (data.error && data.error.length > 0) {
        syntaxError.value = data.error;
      } else {
        emit('show-notification', { message: 'Rule saved successfully!', type: 'success' });
        router.push({ name: 'RuleEdit', params: { id: data.rule_id } });
      }
    })
    .catch(error => {
      console.error('Error:', error);
      emit('show-notification', { message: 'An error occurred while saving the rule.' + error.message, type: 'error' });
    });

}
</script>
