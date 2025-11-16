<template>
    <div class="rule-summary border-2 border-gray-200 p-4 mb-3 rounded-lg">
        
        <div class="flex justify-between items-center mb-2">
            
            <div class="flex items-center space-x-3">
                
                <RouterLink
                    :to="{ name: 'RuleEdit', params: { id: props.rule.id } }"
                    class="btn btn-secondary w-6 h-6"
                    aria-label="Edit rule"
                >
                <button 
                    @click="viewDetails" 
                    class="btn btn-primary w-6 h-6"
                    aria-label="View rule details"
                    >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </button>
                </RouterLink>
                <h2 class="text-lg font-semibold m-0">
                    {{ props.rule.title }}
                </h2>
            </div>
            <div class="flex items-center space-x-4">          
                <div class="relative w-16 h-6 flex items-center justify-end">
                    
                    <div v-if="isUpdating" class="flex items-center space-x-2">
                        <div class="w-4 h-4 border-2 border-blue-400 border-t-transparent rounded-full animate-spin"></div>
                        <span class="text-xs text-gray-500">Wait...</span>
                    </div>

                    <label v-else class="relative inline-flex items-center cursor-pointer">
                        <input 
                            type="checkbox" 
                            class="sr-only peer" 
                            :checked="props.rule.active"
                            @change="toggleStatus" 
                        />
                        
                        <div 
                            class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"
                        ></div>
                    </label>
                </div>
            </div>
            </div>

        <p class="text-gray-700 text-justify">{{ shortDescription }}</p>
    </div>
</template>

<script setup>
import { computed, ref, defineEmits } from 'vue'; 
import RuleEdit from '../views/RuleEdit.vue';

const emit = defineEmits(['update:ruleStatus', 'showDetails']);
const BASE_URL = import.meta.env.VITE_API_BASE_URL;

const props = defineProps({
    rule: {
        type: Object,
        required: true
    }
});

const isUpdating = ref(false); 

const shortDescription = computed(() => {
    const maxChars = 300;
    const description = props.rule.description || '';
    
    if (description.length > maxChars) {
        return description.substring(0, maxChars) + '...';
    }
    return description;
});

const viewDetails = () => {
    emit('showDetails', props.rule.id);
};

const toggleStatus = async () => {
    if (isUpdating.value) return;

    isUpdating.value = true;
    const newStatus = !props.rule.active;
    
    try {
        const response = await fetch(`${BASE_URL}/rules/sigma/${props.rule.id}/status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            },
            body: JSON.stringify({ active: newStatus })
        });

        if (response.ok) {
            props.rule.active = newStatus;
            emit('update:ruleStatus', { ruleId: props.rule.id, newStatus: newStatus });
        } else {
            console.error('API update failed:', await response.text());
        }

    } catch (error) {
        console.error('Network or parsing error:', error);
    } finally {
        isUpdating.value = false;
    }
};
</script>

<style scoped>
.rule-summary h2 {
    margin-top: 0;
    margin-bottom: 0;
}
</style>