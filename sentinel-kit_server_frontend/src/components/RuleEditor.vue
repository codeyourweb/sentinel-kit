<template>
  <div class="monaco-wrapper h-full border border-gray-300 rounded-lg overflow-hidden">
    <DiffEditor 
      v-if="isDiffMode || isLatestDiffToggled"
      :original="originalContent"  
      :modified="modelValue"  
      language="yaml" 
      theme="vs-light" 
      :options="diffOptions"
      class="monaco-container"
    />

    <VueMonacoEditor 
      v-else
      :value="modelValue" 
      @update:value="emit('update:modelValue', $event)" 
      language="yaml" 
      theme="vs-light" 
      :options="editorOptions"
      :readOnly="readOnly"
      class="monaco-container"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { DiffEditor, VueMonacoEditor } from '@guolao/vue-monaco-editor'; 

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  originalContent: {
    type: String,
    default: '',
  },
  isDiffMode: {
    type: Boolean,
    default: false,
  },
  isLatestDiffToggled: {
    type: Boolean,
    default: false,
  },
  readOnly: {
    type: Boolean,
    default: false,
  }
});

const emit = defineEmits(['update:modelValue']);

const editorOptions = computed(() => ({
  automaticLayout: true,
  minimap: { enabled: true },
  readOnly: props.readOnly,
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
</script>

<style scoped>
.monaco-container {
  height: 100%;
  width: 100%;
}
</style>