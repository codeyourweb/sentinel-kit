<template>
    <div class="bg-white overflow-hidden h-full">
        <div v-if="eventsLoading" class="flex justify-center items-center h-64">
            <div class="flex items-center space-x-2 text-orange-600">
                <span class="icon-[svg-spinners--ring-resize] w-8 h-8 animate-spin"></span>
                <span>Loading events...</span>
            </div>
        </div>
        
        <div v-else-if="filteredEvents.length === 0" class="flex justify-center items-center h-64">
            <div class="text-center text-gray-500">
                <span class="icon-[material-symbols--search-off] w-12 h-12 mb-2"></span>
                <p>No events found matching your criteria</p>
            </div>
        </div>
        
        <div v-else class="divide-y divide-gray-100 overflow-y-auto max-h-screen">
            <div 
                v-for="event in filteredEvents" 
                :key="event._id"
                class="p-4 hover:bg-gray-50"
            >
                <div class="flex items-start gap-4 min-w-0 overflow-hidden">
                    <!-- Left: Timestamp badge -->
                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700">
                            {{ formatTimestamp(event._source['@timestamp'] || event._source.timestamp) }}
                        </span>
                    </div>

                    <!-- Center: Content -->
                    <div class="flex-1 min-w-0">
                        <!-- Event content -->
                        <div class="space-y-2 min-w-0 text-left">
                            <!-- Main content field (try to find a meaningful field) -->
                            <div v-if="getMainContent(event._source)" class="text-sm text-gray-900 text-left">
                                <strong class="break-words">{{ getMainContent(event._source) }}</strong>
                            </div>
                            
                            <!-- Other important fields -->
                            <div class="space-y-1 text-xs text-gray-600">
                                <div 
                                    v-for="field in getDisplayFields(event._source)" 
                                    :key="field.key"
                                    class="flex min-w-0 text-left"
                                >
                                    <span class="font-medium text-gray-700 w-20 flex-shrink-0 truncate">{{ field.key }}:</span>
                                    <span class="break-words min-w-0 flex-1 text-left">{{ field.value }}</span>
                                </div>
                            </div>
                            
                            <!-- Expandable raw data -->
                            <div class="mt-2 text-left">
                                <a
                                    @click="toggleEventExpansion(event._id)"
                                    class="btn btn-sm btn-info"
                                    href="#"
                                >
                                    {{ expandedEvents.has(event._id) ? 'Hide' : 'Show' }} raw data
                                </a>
                                
                                <div v-if="expandedEvents.has(event._id)" class="mt-2 min-w-0">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-700">Field</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-700">Value</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                <tr v-for="(value, key) in event._source" :key="key">
                                                    <td class="px-3 py-2 font-mono text-gray-600 text-left">{{ key }}</td>
                                                    <td class="px-3 py-2 break-all text-left">
                                                        <span v-if="typeof value === 'object'">{{ JSON.stringify(value, null, 2) }}</span>
                                                        <span v-else>{{ value }}</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Index badge -->
                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                            {{ event._index }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    events: Array,
    eventsLoading: Boolean,
    filters: Array
})

const expandedEvents = ref(new Set())

const filteredEvents = computed(() => {
    if (!props.events || props.filters.length === 0) {
        return props.events || []
    }
    
    return props.events.filter(event => {
        const source = event._source || {}
        
        return props.filters.every(filter => {
            const fieldValue = source[filter.field]
            const stringValue = typeof fieldValue === 'object' ? JSON.stringify(fieldValue) : String(fieldValue)
            const matches = stringValue === filter.value
            
            return filter.include ? matches : !matches
        })
    })
})

const formatTimestamp = (timestamp) => {
    if (!timestamp) return 'N/A'
    
    try {
        const date = new Date(timestamp)
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        })
    } catch (error) {
        return timestamp
    }
}

const getMainContent = (source) => {
    // Try to find a meaningful content field
    const contentFields = ['message', 'log', 'content', 'description', 'summary', 'event_data', 'raw']
    
    for (const field of contentFields) {
        if (source[field]) {
            const value = source[field]
            if (typeof value === 'string' && value.length > 0) {
                // Truncate to avoid layout issues - shorter limit for better display
                return value.length > 150 ? value.substring(0, 150) + '...' : value
            }
        }
    }
    
    return null
}

const getDisplayFields = (source) => {
    // Skip timestamp and main content fields
    const skipFields = ['@timestamp', 'timestamp', '@version', 'message', 'log', 'content', 'description', 'summary', 'event_data', 'raw']
    const importantFields = ['host', 'source', 'user', 'process', 'service', 'level', 'severity', 'status', 'type', 'category']
    
    const fields = []
    
    // Add important fields first
    importantFields.forEach(fieldName => {
        if (source[fieldName] && !skipFields.includes(fieldName)) {
            const value = source[fieldName]
            const stringValue = typeof value === 'object' ? JSON.stringify(value) : String(value)
            if (stringValue.length > 0) {
                fields.push({
                    key: fieldName,
                    value: stringValue.length > 60 ? stringValue.substring(0, 60) + '...' : stringValue
                })
            }
        }
    })
    
    // Add other fields (limit to avoid clutter)
    let otherFieldsCount = 0
    Object.keys(source).forEach(key => {
        if (!skipFields.includes(key) && 
            !importantFields.includes(key) && 
            source[key] && 
            otherFieldsCount < 3) {
            
            const value = source[key]
            const stringValue = typeof value === 'object' ? JSON.stringify(value) : String(value)
            if (stringValue.length > 0) {
                fields.push({
                    key,
                    value: stringValue.length > 60 ? stringValue.substring(0, 60) + '...' : stringValue
                })
                otherFieldsCount++
            }
        }
    })
    
    return fields
}

const toggleEventExpansion = (eventId) => {
    if (expandedEvents.value.has(eventId)) {
        expandedEvents.value.delete(eventId)
    } else {
        expandedEvents.value.add(eventId)
    }
}
</script>

<style scoped>
pre {
    white-space: pre-wrap;
    word-wrap: break-word;
}

/* Ensure no horizontal overflow in the container */
.events-container {
    overflow-x: hidden;
    max-width: 100%;
}

/* Force text wrapping for long words */
.break-words {
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    hyphens: auto;
}

/* Prevent flex items from shrinking too much */
.flex-shrink-0 {
    min-width: fit-content;
}

/* Ensure proper text truncation */
.truncate-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>