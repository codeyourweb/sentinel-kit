<!--
/**
 * Home View - Platform Dashboard Summary
 * 
 * This is the main dashboard view that provides a comprehensive overview
 * of the Sentinel-Kit security platform. It displays key metrics, recent
 * alerts, service status, and real-time statistics.
 * 
 * Features:
 * - Real-time security metrics with trend indicators
 * - Active alerts summary with severity breakdown
 * - Detection rules status and configuration overview
 * - Recent alerts list with quick access to details
 * - Service status indicators for all platform components
 * - Auto-refresh functionality every 30 seconds
 * - Responsive design for desktop and mobile devices
 * 
 * Data Sources:
 * - Platform metrics from backend APIs
 * - Real-time alert data from Elasticsearch
 * - Service health checks from monitoring endpoints
 * - Detection rules status from rules engine
 */
-->

<template>
    <!-- Dashboard Header with Auto-refresh Status -->
    <div class="flex items-center justify-center space-x-6 text-sm text-gray-500 px-6 py-4 mb-2">
        <div class="flex-1">
            <h1 class="text-2xl text-left font-semibold text-gray-900">
                Platform summary
            </h1>
        </div>
        <div class="flex items-center space-x-6">
            <div class="flex items-center space-x-2">
                <span class="icon-[material-symbols--update] w-4 h-4" :class="{ 'animate-spin': loading }"></span>
                <span>Last updated: {{ loading ? 'Updating...' : lastUpdated }}</span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="icon-[material-symbols--schedule] w-4 h-4"></span>
                <span>Auto-refresh: 30s</span>
            </div>
        </div>
    </div>

    <div class="min-h-screen bg-gray-50">
        <div class="px-6 space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start lg:items-stretch">
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 h-full">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">Security Metrics Overview</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <!-- Total Events -->
                            <div class="bg-gradient-to-r from-blue-100 to-blue-200 border border-blue-300 p-6 rounded-lg shadow-sm">
                                <div v-if="loading" class="animate-pulse">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="h-4 bg-blue-300 rounded w-32 mb-3"></div>
                                            <div class="h-8 bg-blue-300 rounded w-24 mb-2"></div>
                                            <div class="h-3 bg-blue-300 rounded w-20"></div>
                                        </div>
                                        <div class="w-10 h-10 bg-blue-300 rounded"></div>
                                    </div>
                                </div>
                                <div v-else class="flex items-center justify-between">
                                    <div>
                                        <p class="text-blue-700 text-sm font-medium">Total Events (24h)</p>
                                        <p class="text-3xl font-bold text-gray-800">{{ formatNumber(totalEvents) }}</p>
                                        <p class="text-blue-600 text-xs mt-1">
                                            <span :class="eventsTrend >= 0 ? 'text-green-600' : 'text-red-600'">
                                                {{ eventsTrend >= 0 ? '↗' : '↘' }} {{ Math.abs(eventsTrend) }}%
                                            </span>
                                            vs yesterday
                                        </p>
                                    </div>
                                    <span class="icon-[material-symbols--monitoring] w-10 h-10 text-blue-500"></span>
                                </div>
                            </div>

                            <!-- Active Alerts -->
                            <div class="bg-gradient-to-r from-rose-100 to-pink-200 border border-rose-300 p-6 rounded-lg shadow-sm">
                                <div v-if="loading" class="animate-pulse">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="h-4 bg-rose-300 rounded w-28 mb-3"></div>
                                            <div class="h-8 bg-rose-300 rounded w-16 mb-2"></div>
                                            <div class="h-3 bg-rose-300 rounded w-24"></div>
                                        </div>
                                        <div class="w-10 h-10 bg-rose-300 rounded"></div>
                                    </div>
                                </div>
                                <div v-else class="flex items-center justify-between">
                                    <div>
                                        <p class="text-rose-700 text-sm font-medium">Active Alerts</p>
                                        <p class="text-3xl font-bold text-gray-800">{{ activeAlerts }}</p>
                                        <p class="text-rose-600 text-xs mt-1">
                                            <span class="text-orange-600">{{ criticalAlerts }} critical</span>
                                        </p>
                                    </div>
                                    <span class="icon-[material-symbols--warning] w-10 h-10 text-rose-500"></span>
                                </div>
                            </div>

                            <!-- Detection Rules -->
                            <div class="bg-gradient-to-r from-purple-100 to-violet-200 border border-purple-300 p-6 rounded-lg shadow-sm">
                                <div v-if="loading" class="animate-pulse">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="h-4 bg-purple-300 rounded w-32 mb-3"></div>
                                            <div class="h-8 bg-purple-300 rounded w-20 mb-2"></div>
                                            <div class="h-3 bg-purple-300 rounded w-24"></div>
                                        </div>
                                        <div class="w-10 h-10 bg-purple-300 rounded"></div>
                                    </div>
                                </div>
                                <div v-else class="flex items-center justify-between">
                                    <div>
                                        <p class="text-purple-700 text-sm font-medium">Detection Rules</p>
                                        <p class="text-3xl font-bold text-gray-800">{{ activeRules }}</p>
                                        <p class="text-purple-600 text-xs mt-1">{{ enabledRulesPercent }}% enabled</p>
                                    </div>
                                    <span class="icon-[material-symbols--rule] w-10 h-10 text-purple-500"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Infrastructure Status -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 h-full flex flex-col">
                    <div class="px-6 py-4 border-b border-gray-200 flex-shrink-0">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Infrastructure Status</h2>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 flex-1">
                        <div v-if="loading" class="animate-pulse space-y-4">
                            <div class="h-16 bg-gray-200 rounded-lg"></div>
                            <div class="h-16 bg-gray-200 rounded-lg"></div>
                            <div class="h-16 bg-gray-200 rounded-lg"></div>
                            <div class="h-16 bg-gray-200 rounded-lg"></div>
                        </div>
                        <div v-else class="services-single-column h-full">
                            <ServiceStatus @services-loaded="handleServicesLoaded" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900">Last alerts</h2>
                        <router-link :to="{ path: '/alerts', query: getLast24HoursParams() }" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            View All Alerts →
                        </router-link>
                    </div>
                </div>
                <div class="p-6">
                    <div v-if="loading" class="animate-pulse">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Alert
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Severity
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Time
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="i in 3" :key="i">
                                        <td class="px-6 py-4">
                                            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="h-6 bg-gray-200 rounded-full w-16"></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="h-4 bg-gray-200 rounded w-20"></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="h-6 bg-gray-200 rounded w-12"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div v-else-if="recentAlerts.length === 0" class="text-center py-8 text-gray-500">
                        <span class="icon-[material-symbols--check-circle] w-12 h-12 mx-auto mb-3 text-green-500"></span>
                        <p class="text-lg font-medium text-gray-900 mb-2">All Clear</p>
                        <p>No recent security alerts detected in the last 24 hours</p>
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Alert
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Severity
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Time
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="alert in recentAlerts" :key="alert.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-left">
                                        <div class="text-sm font-medium text-gray-900 text-left">
                                            <a 
                                                @click="navigateToAlert(alert.rule_id)"
                                                class="text-orange-400 hover:text-orange-300 cursor-pointer underline"
                                                href="#"
                                            >
                                                {{ alert.title }}
                                            </a>
                                        </div>
                                        <div class="text-sm text-gray-500 text-left">{{ alert.description }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full" :class="getSeverityClass(alert.severity)">
                                            {{ alert.severity }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-left">
                                        {{ formatDate(alert.timestamp) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <a 
                                            @click="navigateToAlert(alert.rule_id)"
                                            class="btn btn-primary text-xs px-3 py-1 rounded flex items-center gap-1"
                                        >
                                            <span class="icon-[material-symbols--undereye] bg-white text-white"></span>
                                            Show
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>


<script setup>
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import ServiceStatus from '../components/ServiceStatus.vue'
import { useAlertUtils } from '../composables/useAlertUtils'

const emit = defineEmits(['show-notification'])
const router = useRouter()
const BASE_URL = import.meta.env.VITE_API_BASE_URL

// Import utility functions from AlertsList
const { getSeverityClass, formatDate } = useAlertUtils()

// Reactive state for dashboard metrics
const dashboardData = reactive({
    totalEvents: 0,
    activeAlerts: 0,
    criticalAlerts: 0,
    registeredAssets: 1624, // Static value for now
    activeRules: 0,
    totalRules: 0,
    eventsTrend: 0,
    recentAlerts: []
})

// Loading states
const loading = ref(false)
const lastUpdated = ref('')
const refreshInterval = ref(null)

// Computed properties
const enabledRulesPercent = computed(() => {
    if (dashboardData.totalRules === 0) return 0
    return Math.round((dashboardData.activeRules / dashboardData.totalRules) * 100)
})

// Utility methods
const formatNumber = (num) => {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M'
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K'
    }
    return num.toString()
}

// Data loading methods
const loadDashboardMetrics = async () => {
    loading.value = true
    try {
        // Load all dashboard stats from new endpoint
        const response = await fetch(`${BASE_URL}/dashboard/stats`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        })

        if (response.ok) {
            const data = await response.json()
            
            // Update dashboard data with real values
            dashboardData.totalEvents = data.total_events_24h
            dashboardData.eventsTrend = data.events_trend
            dashboardData.activeAlerts = data.active_alerts.total
            dashboardData.criticalAlerts = data.active_alerts.critical
            dashboardData.activeRules = data.detection_rules.active
            dashboardData.totalRules = data.detection_rules.total
        }
        
        // Load recent alerts separately
        await loadRecentAlerts()
        
        lastUpdated.value = new Date().toLocaleTimeString()
    } catch (error) {
        console.error('Error loading dashboard metrics:', error)
        emit('show-notification', {
            type: 'error',
            message: 'Failed to load dashboard metrics'
        })
    } finally {
        loading.value = false
    }
}

const loadRecentAlerts = async () => {
    try {
        const response = await fetch(`${BASE_URL}/dashboard/recent-alerts?limit=5`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        })

        if (response.ok) {
            const alerts = await response.json()
            dashboardData.recentAlerts = alerts
        }
    } catch (error) {
        console.error('Error loading recent alerts:', error)
        dashboardData.recentAlerts = []
    }
}

// Helper function to get 24h date range
const getLast24HoursParams = () => {
    const endTime = new Date()
    const startTime = new Date(Date.now() - 24 * 60 * 60 * 1000)
    
    // Format dates as YYYY-MM-DDTHH:mm (same format as in the URL example)
    const formatDate = (date) => {
        return date.getFullYear() + '-' + 
               String(date.getMonth() + 1).padStart(2, '0') + '-' + 
               String(date.getDate()).padStart(2, '0') + 'T' + 
               String(date.getHours()).padStart(2, '0') + ':' + 
               String(date.getMinutes()).padStart(2, '0')
    }
    
    return {
        startDate: formatDate(startTime),
        endDate: formatDate(endTime),
        showAlertsOnly: true,
        chartCollapsed: false
    }
}

// Navigation method for alerts
const navigateToAlert = (ruleId) => {
    const params = getLast24HoursParams()
    // Add rule filter if provided
    if (ruleId) {
        params.ruleFilter = ruleId
    }
    
    router.push({
        path: '/alerts',
        query: params
    })
}

const handleServicesLoaded = (servicesData) => {
    dashboardData.totalServices = servicesData.length
    dashboardData.servicesUp = servicesData.filter(service => service.status === 'ok').length
}

const refreshServices = () => {
    loadDashboardMetrics()
}

const openSIEM = () => {
    emit('show-notification', {
        type: 'info',
        message: 'SIEM dashboard will be available in a future update'
    })
}

// Lifecycle
onMounted(async () => {
    await loadDashboardMetrics()
    
    // Auto-refresh every 30 seconds
    refreshInterval.value = setInterval(loadDashboardMetrics, 30000)
})

onUnmounted(() => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value)
    }
})

// Properties exposed to template  
const totalEvents = computed(() => dashboardData.totalEvents)
const activeAlerts = computed(() => dashboardData.activeAlerts)
const criticalAlerts = computed(() => dashboardData.criticalAlerts) 
const registeredAssets = computed(() => dashboardData.registeredAssets)
const activeRules = computed(() => dashboardData.activeRules)
const eventsTrend = computed(() => dashboardData.eventsTrend)
const recentAlerts = computed(() => dashboardData.recentAlerts)
</script>

<style scoped>
/* Force ServiceStatus to display in single column within Infrastructure Status panel */
.services-single-column :deep(.grid) {
    display: flex !important;
    flex-direction: column !important;
    gap: 1rem !important;
}

.services-single-column :deep(.grid > div) {
    width: 100% !important;
}
</style>