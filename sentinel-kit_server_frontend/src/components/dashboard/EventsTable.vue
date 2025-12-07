<template>
    <div class="bg-white rounded-lg shadow-lg">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-800">Recent Events</h2>
                <span class="text-sm text-gray-600">
                    {{ props.events.length }} events displayed
                </span>
            </div>
        </div>
        
        <div v-if="eventsLoading" class="p-8 text-center">
            <div class="flex justify-center items-center space-x-2 text-orange-600">
                <span class="icon-[svg-spinners--ring-resize] w-6 h-6 animate-spin"></span>
                <span>Loading events...</span>
            </div>
        </div>
        
        <div v-else-if="props.events.length === 0" class="p-8 text-center text-gray-500">
            <span class="icon-[material-symbols--info] w-6 h-6 mb-2"></span>
            <p>No events found for the selected criteria.</p>
        </div>
        
        <div v-else class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <button 
                                @click="$emit('sort', '@timestamp')"
                                class="flex items-center gap-1 hover:text-gray-700"
                            >
                                Timestamp
                                <span v-if="sortField === '@timestamp'" :class="sortDirection === 'asc' ? 'icon-[material-symbols--arrow-upward]' : 'icon-[material-symbols--arrow-downward]'" class="w-3 h-3"></span>
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <button 
                                @click="$emit('sort', 'index')"
                                class="flex items-center gap-1 hover:text-gray-700"
                            >
                                Index
                                <span v-if="sortField === 'index'" :class="sortDirection === 'asc' ? 'icon-[material-symbols--arrow-upward]' : 'icon-[material-symbols--arrow-downward]'" class="w-3 h-3"></span>
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Content</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template v-for="(event, index) in props.events" :key="event._id || index">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900 text-left font-mono">
                                {{ formatDate(event._source['@timestamp'] || event._source.timestamp) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 text-left">
                                <div class="font-medium text-blue-600">{{ event._index }}</div>
                            </td>
                            <td class="px-6 py-4 text-left">
                                <div class="text-sm text-gray-900 max-w-lg">
                                    {{ getEventContent(event._source) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <a 
                                    @click="toggleEventDetails(event._id || index)"
                                    class="btn btn-primary text-xs px-3 py-1 rounded flex items-center gap-1"
                                >
                                    <span class="icon-[material-symbols--expand-more] bg-white text-white transition-transform" 
                                          :class="{ 'rotate-180': expandedEvents.has(event._id || index) }">
                                    </span>
                                    <span class="icon-[material-symbols--undereye] bg-white text-white"></span>
                                </a>
                            </td>
                        </tr>
                        <tr v-if="expandedEvents.has(event._id || index)" class="bg-gray-50">
                            <td colspan="4" class="px-6 py-4">
                                <div class="bg-white rounded-lg p-4 border">
                                    <h4 class="font-semibold text-gray-900 mb-3">Event Raw Data</h4>
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
                                                    <td class="px-3 py-2 font-mono text-gray-600">{{ key }}</td>
                                                    <td class="px-3 py-2 break-all">
                                                        <span v-if="typeof value === 'object'">{{ JSON.stringify(value, null, 2) }}</span>
                                                        <span v-else>{{ value }}</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAlertUtils } from '../../composables/useAlertUtils'

const props = defineProps({
    events: {
        type: Array,
        default: () => []
    },
    eventsLoading: {
        type: Boolean,
        default: false
    },
    startDate: String,
    endDate: String,
    searchFilter: String,
    sortField: {
        type: String,
        default: '@timestamp'
    },
    sortDirection: {
        type: String,
        default: 'desc'
    }
})

const emit = defineEmits(['sort'])

const { formatDate } = useAlertUtils()

// Local state
const expandedEvents = ref(new Set())

// Methods
const toggleEventDetails = (eventId) => {
    if (expandedEvents.value.has(eventId)) {
        expandedEvents.value.delete(eventId)
    } else {
        expandedEvents.value.add(eventId)
    }
}

const getEventContent = (source) => {
    // Convert the entire source object to a JSON string and take first 500 chars
    const jsonString = JSON.stringify(source, null, 2)
    return jsonString.length > 500 ? jsonString.substring(0, 500) + '...' : jsonString
}
</script>