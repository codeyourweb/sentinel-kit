<template>
  <div>
    <h3 class="text-left text-lg font-semibold mb-4 text-gray-700">New sigma rule creation</h3>
    <RuleEditor 
      v-model="newRuleContent"
      :read-only="false"
      class="h-96 w-full"
    />
    <button @click="saveRule">Save</button>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import RuleEditor from '../components/RuleEditor.vue';

const newRuleContent = ref('# https://github.com/SigmaHQ/sigma/wiki/Rule-Creation-Guide\n\ntitle: a short capitalised title with less than 50 characters\nid: generate one here https://www.uuidgenerator.net/version4\nstatus: experimental\ndescription: A description of what your rule is meant to detect \nreferences:\n    - A list of all references that can help a reader or analyst understand the meaning of a triggered rule\ntags:\n    - attack.execution  # example MITRE ATT&CK category\n    - attack.t1059      # example MITRE ATT&CK technique id\n    - car.2014-04-003   # example CAR id\nauthor: Michael Haag, Florian Roth, Markus Neis  # example, a list of authors\ndate: 2018/04/06  # Rule date\nlogsource:                      # important for the field mapping in predefined or your additional config files\n    category: process_creation  # In this example we choose the category \'process_creation\'\n    product: windows            # the respective product\ndetection:\n    selection:\n        FieldName: \'StringValue\'\n        FieldName: IntegerValue\n        FieldName|modifier: \'Value\'\n    condition: selection\nfields:\n    - fields in the log source that are important to investigate further\nfalsepositives:\n    - describe possible false positive conditions to help the analysts in their investigation\nlevel: one of five levels (informational, low, medium, high, critical)');

function saveRule() {
  console.log('Contenu de la nouvelle règle:', newRuleContent.value);
}
</script>
