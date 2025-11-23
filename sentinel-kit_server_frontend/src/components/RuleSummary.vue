<template>
    <div 
        class="rule-summary border-l-4 p-4 mb-4 rounded-lg transition-all duration-300 shadow-sm hover:shadow-md"
        :class="{
            'border-green-500 bg-white hover:bg-gray-50': props.rule.active && !props.isDeleting,
            'border-gray-400 bg-gray-100 hover:bg-gray-200': !props.rule.active && !props.isDeleting,
            'border-gray-300 bg-gray-200 opacity-50 cursor-not-allowed': props.isDeleting
        }"
    >
        
        <div class="flex justify-between items-center mb-3">
            
            <div class="flex items-center space-x-3 min-w-0">
                
                <RouterLink
                    :to="{ name: 'RuleEdit', params: { id: props.rule.id } }"
                    class="p-2 rounded-full text-indigo-600 hover:bg-indigo-100 transition duration-150"
                    :class="{ 'pointer-events-none opacity-50': props.isDeleting }"
                    aria-label="Edit rule"
                    title="Edit rule"
                >
                    <span class="icon-[material-symbols--pageview-outline] w-5 h-5"></span> 
                </RouterLink>
                
                <h2 class="text-xl font-bold truncate">
                    <RouterLink 
                        :to="{ name: 'RuleEdit', params: { id: props.rule.id } }" 
                        class="text-gray-800 hover:text-indigo-600 transition duration-150"
                        :class="{ 'pointer-events-none text-gray-400': props.isDeleting }"
                    >
                        {{ props.rule.title }}
                    </RouterLink>
                </h2>
            </div>
            
            <div class="flex items-center space-x-4 flex-shrink-0">
                


                <div class="relative w-16 h-6 flex items-center justify-end">
                    
                    <div v-if="isUpdating" class="flex items-center space-x-2">
                        <span class="icon-[svg-spinners--ring-resize] w-6 h-6 text-blue-500 animate-spin"></span>
                    </div>

                    <label v-else class="relative inline-flex items-center cursor-pointer" :class="{ 'opacity-50 pointer-events-none': props.isDeleting }">
                        <input 
                            type="checkbox" 
                            class="sr-only peer" 
                            :checked="props.rule.active"
                            :disabled="props.isDeleting"
                            @change="toggleStatus" 
                        />
                        
                        <div 
                            class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"
                        ></div>
                    </label>
                </div>
            </div>
        </div>
        
        <p class="text-sm text-gray-600 mb-4 text-justify">{{ shortDescription }}</p>
        
        <div class="flex justify-between items-center">
            
            <div class="flex space-x-4 text-xs text-gray-500 font-medium">
                <span class="mr-6">Detections: </span>
                <span class="flex items-center space-x-1">
                    <span class="icon-[solar--clock-circle-broken] w-4 h-4 text-blue-500"></span>
                    <span class="font-bold text-gray-700">{{ props.rule.detections_24h || 0 }}</span>
                    <span>(24h)</span>
                </span>
                <span class="flex items-center space-x-1">
                    <span class="icon-[solar--calendar-mark-bold] w-4 h-4 text-blue-500"></span>
                    <span class="font-bold text-gray-700">{{ props.rule.detections_7d || 0 }}</span>
                    <span>(7j)</span>
                </span>
                <span class="flex items-center space-x-1">
                    <span class="icon-[solar--calendar-bold] w-4 h-4 text-blue-500"></span>
                    <span class="font-bold text-gray-700">{{ props.rule.detections_30d || 0 }}</span>
                    <span>(30j)</span>
                </span>
            </div>

            <a
                @click.prevent="props.isDeleting ? null : showDeleteConfirmation = true"
                class="btn btn-soft btn-error btn-sm flex items-center text-sm font-medium text-red-600 hover:bg-red-50 p-2 rounded-lg transition duration-150"
                :class="{ 'opacity-50 cursor-not-allowed': props.isDeleting }"
                title="Delete Rule"
            >
                <span class="icon-[material-symbols--delete-outline] bg-red-600 w-5 h-5 mr-1 text-red-200"></span>
            </a>
        </div>
    </div>

    <!-- Confirmation Modal Overlay -->
    <div 
        v-if="showDeleteConfirmation" 
        class="flex fixed inset-0 bg-gray-200 bg-opacity-80 backdrop-opacity-80 items-center justify-center z-50"
        @click="showDeleteConfirmation = false"
    >
        <div 
            class="bg-white rounded-lg p-6 max-w-md mx-4 shadow-xl"
            @click.stop
        >
            <div class="flex items-center mb-4">
                <span class="icon-[material-symbols--warning] w-6 h-6 text-red-500 mr-3"></span>
                <h3 class="text-lg font-semibold text-gray-900">Confirm deletion</h3>
            </div>
            
            <p class="text-gray-600 mb-6">
                Are you sure you want to delete the rule "<strong>{{ props.rule.title }}</strong>"? 
                This action is irreversible.
            </p>
            
            <div class="flex justify-end space-x-3">
                <a
                    @click="showDeleteConfirmation = false"
                    class="btn btn-error px-4 py-2 text-gray-600 bg-gray-200 hover:bg-gray-300 rounded-lg transition duration-150"
                >
                    Cancel
                </a>
                <a
                    @click="confirmDelete"
                    class="btn btn-primary px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-lg transition duration-150"
                >
                    Remove
                </a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, defineEmits } from 'vue'; 
import { RouterLink } from 'vue-router';

const emit = defineEmits(['update:ruleStatus', 'showDetails', 'deleteRule', 'show-notification']); 
const BASE_URL = import.meta.env.VITE_API_BASE_URL;

const props = defineProps({
    rule: {
        type: Object,
        required: true
    },
    isDeleting: {
        type: Boolean,
        default: false
    }
});

const isUpdating = ref(false); 
const showDeleteConfirmation = ref(false); 

const shortDescription = computed(() => {
    const maxChars = 300;
    const description = props.rule.description || '';
    
    if (description.length > maxChars) {
        return description.substring(0, maxChars) + '...';
    }
    return description;
});

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
        } else {
            console.error('API update failed:', await response.text());
        }

    } catch (error) {
        console.error('Network or parsing error:', error);
    } finally {
        isUpdating.value = false;
    }
};

const confirmDelete = () => {
    showDeleteConfirmation.value = false;
    emit('deleteRule', props.rule.id);
};
</script>