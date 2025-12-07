<template>
    <div class="bg-white rounded-lg shadow-lg">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-800">Recent Alerts</h2>
                <span class="text-sm text-gray-600">
                    {{ props.paginatedAlerts.length }} alerts displayed
                </span>
            </div>
        </div>
        
        <div v-if="alertsLoading" class="animate-pulse">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alert</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="i in 8" :key="i" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-left">
                                <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="h-5 bg-gray-200 rounded-full w-16"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="h-4 bg-gray-200 rounded w-20"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="h-6 bg-gray-200 rounded w-16"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div v-else-if="props.alerts.length === 0" class="p-8 text-center text-gray-500">
            <span class="icon-[material-symbols--info] w-6 h-6 mb-2"></span>
            <p>No alerts found for the selected criteria.</p>
        </div>
        
        <div v-else class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a 
                                @click="$emit('sort', 'title')"
                                class="flex items-center gap-1 hover:text-gray-700 cursor-pointer"
                            >
                                Alert
                                <span v-if="sortField === 'title'" :class="sortDirection === 'asc' ? 'icon-[material-symbols--arrow-upward]' : 'icon-[material-symbols--arrow-downward]'" class="w-3 h-3"></span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a  
                                @click="$emit('sort', 'level')"
                                class="flex items-center gap-1 hover:text-gray-700 cursor-pointer"
                            >
                                Severity
                                <span v-if="sortField === 'level'" :class="sortDirection === 'asc' ? 'icon-[material-symbols--arrow-upward]' : 'icon-[material-symbols--arrow-downward]'" class="w-3 h-3"></span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a 
                                @click="$emit('sort', 'alertTime')"
                                class="flex items-center gap-1 hover:text-gray-700 cursor-pointer"
                            >
                                Time
                                <span v-if="sortField === 'alertTime'" :class="sortDirection === 'asc' ? 'icon-[material-symbols--arrow-upward]' : 'icon-[material-symbols--arrow-downward]'" class="w-3 h-3"></span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template v-for="alert in props.paginatedAlerts" :key="alert.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-left">
                                <div class="text-sm font-medium text-gray-900 text-left">
                                    <a 
                                        @click="$emit('view-rule', alert)"
                                        class="text-orange-400 hover:text-orange-300 cursor-pointer underline"
                                        href="#"
                                    >
                                        {{ alert.sigmaRule?.title || 'Unknown Rule' }}
                                    </a>
                                </div>
                                <div class="text-sm text-gray-500 text-left">{{ alert.sigmaRule?.description || 'No description' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full" :class="getSeverityClass(alert.sigmaRule?.level)">
                                    {{ alert.sigmaRule?.level || 'unknown' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 text-left">
                                {{ formatDate(alert.alertTime) }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <a 
                                    @click="$emit('toggle-details', alert.id)"
                                    class="btn btn-primary text-xs px-3 py-1 rounded flex items-center gap-1"
                                >
                                    <span class="icon-[material-symbols--expand-more] bg-white text-white transition-transform" 
                                          :class="{ 'rotate-180': props.expandedAlerts.has(alert.id) }">
                                    </span>
                                    <span class="icon-[material-symbols--undereye] bg-white text-white"></span>
                                </a>
                            </td>
                        </tr>
                        <tr v-if="props.expandedAlerts.has(alert.id)" class="bg-gray-50">
                            <td colspan="4" class="px-6 py-4">
                                <div class="bg-white rounded-lg p-4 border">
                                    <h4 class="font-semibold text-gray-900 mb-3">Event Details</h4>
                                    <div v-if="props.alertDetails[alert.id]?.loading" class="text-center py-4">
                                        <span class="icon-[svg-spinners--ring-resize] w-4 h-4 animate-spin mr-2"></span>
                                        Loading event details...
                                    </div>
                                    <div v-else-if="props.alertDetails[alert.id]?.error" class="text-red-600 text-center py-4">
                                        Error loading event details: {{ props.alertDetails[alert.id].error }}
                                    </div>
                                    <div v-else-if="props.alertDetails[alert.id]?.data" class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-700">Field</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-700">Value</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                <tr v-for="(value, key) in props.alertDetails[alert.id].data" :key="key">
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
import { reactive, ref, computed } from 'vue'
import { useAlertUtils } from '../../composables/useAlertUtils'

const props = defineProps({
    alerts: Array,
    paginatedAlerts: Array,
    alertsLoading: Boolean,
    expandedAlerts: Set,
    alertDetails: Object,
    sortField: {
        type: String,
        default: 'alertTime'
    },
    sortDirection: {
        type: String,
        default: 'desc'
    }
})

const emit = defineEmits(['toggle-details', 'view-rule', 'sort'])

const { getSeverityClass, formatDate } = useAlertUtils()
</script>