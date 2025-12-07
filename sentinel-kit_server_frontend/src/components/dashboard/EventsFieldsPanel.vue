<template>
    <div class="bg-white h-full flex flex-col">
        <!-- Fixed Header: Available Fields (changes based on collapsed state) -->
        <div v-if="!collapsed" class="p-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
            <h3 class="text-lg font-semibold text-gray-800">Available Fields</h3>
            <a
                @click="$emit('toggle-collapse')"
                class="btn btn-sm btn-info flex items-center gap-2"
                href="#"
                title="Hide Available Fields"
            >
                <span class="icon-[material-symbols--chevron-left] transition-transform duration-300"></span>
                <span>Hide</span>
            </a>
        </div>

        <!-- Collapsed Header: Vertical text with small expand button -->
        <div v-else class="h-full flex flex-col items-center justify-start p-2 border-b border-gray-200">
            <!-- Vertical "Available Fields" text -->
            <div class="writing-mode-vertical flex-1 flex items-center justify-center">
                <span class="text-sm font-semibold text-gray-800 whitespace-nowrap vertical-text">
                    Available Fields
                </span>
            </div>
            
            <!-- Small expand button at bottom -->
            <a
                @click="$emit('toggle-collapse')"
                class="btn btn-xs btn-info mt-2"
                href="#"
                title="Show Available Fields"
            >
                <span class="icon-[material-symbols--chevron-right] w-3 h-3"></span>
            </a>
        </div>
        
        <!-- Fixed Active Filters Section (always visible when not collapsed and has filters) -->
        <div v-if="activeFilters.length > 0 && !collapsed" class="border-b border-gray-200 p-4 flex-shrink-0">
            <h4 class="text-sm font-semibold text-gray-800 mb-2">Active Filters</h4>
            <div class="space-y-1">
                <div 
                    v-for="(filter, index) in activeFilters" 
                    :key="index"
                    class="flex items-center justify-between text-xs bg-gray-100 rounded px-2 py-1"
                >
                    <span class="truncate">
                        <span class="font-medium">{{ filter.field }}:</span>
                        <span :class="filter.include ? 'text-green-700' : 'text-red-700'">
                            {{ filter.include ? '' : '!' }}{{ filter.value }}
                        </span>
                    </span>
                    <a
                        @click="removeFilter(index)"
                        class="btn btn-sm btn-info"
                        href="#"
                    >
                        <span class="icon-[material-symbols--close] w-3 h-3"></span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Scrollable Content Area -->
        <div v-if="!collapsed" class="flex-1 scrollable-content p-4" style="max-height: calc(100vh - 200px);">
            <div v-if="loading" class="animate-pulse space-y-3">
                <div v-for="i in 8" :key="i" class="border border-gray-200 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-2">
                        <div class="h-4 bg-gray-200 rounded w-24"></div>
                        <div class="h-3 bg-gray-200 rounded w-8"></div>
                    </div>
                    <div class="mb-2">
                        <div class="w-full bg-gray-200 rounded-full h-2"></div>
                    </div>
                    <div class="space-y-1">
                        <div v-for="j in 3" :key="j" class="flex items-center justify-between py-1">
                            <div class="h-3 bg-gray-200 rounded flex-1 mr-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-8"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div v-else class="space-y-3">
                <div 
                    v-for="field in sortedFields" 
                    :key="field.key"
                    class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-900">{{ field.key }}</span>
                        <span class="text-xs text-gray-500">{{ field.percentage }}%</span>
                    </div>
                    
                    <div class="mb-2">
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div 
                                class="bg-orange-500 h-1.5 rounded-full" 
                                :style="{ width: field.percentage + '%' }"
                            ></div>
                        </div>
                    </div>
                    
                    <div class="space-y-1">
                        <div 
                            v-for="value in field.values.slice(0, field.expanded ? field.values.length : 5)" 
                            :key="value.value"
                            class="flex items-center justify-between text-xs"
                        >
                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                <a
                                    @click="addFilter(field.key, value.value, true)"
                                    class="btn btn-sm btn-info"
                                    href="#"
                                    title="Include"
                                >
                                    <span class="icon-[material-symbols--add] w-3 h-3"></span>
                                </a>
                                <a
                                    @click="addFilter(field.key, value.value, false)"
                                    class="btn btn-sm btn-info"
                                    href="#"
                                    title="Exclude"
                                >
                                    <span class="icon-[material-symbols--remove] w-3 h-3"></span>
                                </a>
                                <span class="text-gray-600 truncate">{{ value.value }}</span>
                            </div>
                            <span class="text-gray-400 flex-shrink-0">{{ value.count }}</span>
                        </div>
                        
                        <a
                            v-if="field.values.length > 5"
                            @click="toggleFieldExpansion(field.key)"
                            class="btn btn-sm btn-info"
                            href="#"
                        >
                            {{ field.expanded ? 'Show less' : `Show ${field.values.length - 5} more` }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
    events: Array,
    loading: Boolean,
    collapsed: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['filters-changed', 'toggle-collapse'])

const expandedFields = ref(new Set())
const activeFilters = ref([])

const fieldsData = computed(() => {
    if (!props.events || props.events.length === 0) return {}
    
    const fields = {}
    const totalEvents = props.events.length
    
    props.events.forEach(event => {
        const source = event._source || {}
        
        Object.keys(source).forEach(key => {
            if (['@timestamp', 'timestamp', '@version'].includes(key)) return
            
            if (!fields[key]) {
                fields[key] = {}
            }
            
            const value = source[key]
            const stringValue = typeof value === 'object' ? JSON.stringify(value) : String(value)
            
            if (!fields[key][stringValue]) {
                fields[key][stringValue] = 0
            }
            fields[key][stringValue]++
        })
    })
    
    // Convert to array format with percentages
    const result = {}
    Object.keys(fields).forEach(fieldKey => {
        const fieldTotal = Object.values(fields[fieldKey]).reduce((sum, count) => sum + count, 0)
        const percentage = Math.round((fieldTotal / totalEvents) * 100)
        
        const values = Object.entries(fields[fieldKey])
            .map(([value, count]) => ({ value, count }))
            .sort((a, b) => b.count - a.count)
        
        result[fieldKey] = {
            key: fieldKey,
            percentage,
            total: fieldTotal,
            values,
            expanded: expandedFields.value.has(fieldKey)
        }
    })
    
    return result
})

const sortedFields = computed(() => {
    return Object.values(fieldsData.value)
        .sort((a, b) => b.percentage - a.percentage)
})

const toggleFieldExpansion = (fieldKey) => {
    if (expandedFields.value.has(fieldKey)) {
        expandedFields.value.delete(fieldKey)
    } else {
        expandedFields.value.add(fieldKey)
    }
}

const addFilter = (field, value, include) => {
    const existingIndex = activeFilters.value.findIndex(
        f => f.field === field && f.value === value
    )
    
    if (existingIndex >= 0) {
        activeFilters.value[existingIndex].include = include
    } else {
        activeFilters.value.push({ field, value, include })
    }
    
    emit('filters-changed', activeFilters.value)
}

const removeFilter = (index) => {
    activeFilters.value.splice(index, 1)
    emit('filters-changed', activeFilters.value)
}

// Watch for events changes to reset expanded state
watch(() => props.events, () => {
    expandedFields.value.clear()
})
</script>

<style scoped>
.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Force scrollbar visibility */
.scrollable-content {
    overflow-y: scroll !important;
    scrollbar-width: thin;
    scrollbar-color: #d1d5db #f3f4f6;
}

.scrollable-content::-webkit-scrollbar {
    width: 8px;
}

.scrollable-content::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 4px;
}

.scrollable-content::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}

.scrollable-content::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

/* Vertical text styling for collapsed mode */
.vertical-text {
    writing-mode: vertical-rl;
    text-orientation: mixed;
    transform: rotate(180deg);
}

.writing-mode-vertical {
    writing-mode: vertical-rl;
}
</style>