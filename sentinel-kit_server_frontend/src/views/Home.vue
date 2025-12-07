<template>
    <div class="flex items-center justify-center space-x-6 text-sm text-gray-500 px-6 py-4 mb-2">
        <div class="flex-1">
            <h1 class="text-2xl text-left font-semibold text-gray-900">
                Platform summary
            </h1>
        </div>
        <div class="flex items-center space-x-6">
            <div class="flex items-center space-x-2">
                <span class="icon-[material-symbols--update] w-4 h-4"></span>
                <span>Last updated: {{ lastUpdated }}</span>
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
                                <div class="flex items-center justify-between">
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
                                <div class="flex items-center justify-between">
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
                                <div class="flex items-center justify-between">
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
                        <div class="services-single-column h-full">
                            <ServiceStatus @services-loaded="handleServicesLoaded" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900">Recent Activity</h2>
                        <router-link to="/alerts" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            View All Events →
                        </router-link>
                    </div>
                </div>
                <div class="p-6">
                    <div v-if="recentAlerts.length === 0" class="text-center py-8 text-gray-500">
                        <span class="icon-[material-symbols--check-circle] w-12 h-12 mx-auto mb-3 text-green-500"></span>
                        <p class="text-lg font-medium text-gray-900 mb-2">All Clear</p>
                        <p>No recent security alerts detected in the last 24 hours</p>
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="alert in recentAlerts" :key="alert.id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border">
                            <div class="flex items-center space-x-4">
                                <div :class="getSeverityIndicator(alert.severity)" class="w-3 h-3 rounded-full flex-shrink-0"></div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ alert.title }}</p>
                                    <p class="text-sm text-gray-600">{{ alert.description }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">{{ formatTime(alert.timestamp) }}</p>
                                <span :class="getSeverityBadge(alert.severity)" class="inline-flex px-2 py-1 text-xs font-medium rounded-full">
                                    {{ alert.severity }}
                                </span>
                            </div>
                        </div>
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

const emit = defineEmits(['show-notification'])
const router = useRouter()
const BASE_URL = import.meta.env.VITE_API_BASE_URL

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

const formatTime = (timestamp) => {
    const date = new Date(timestamp)
    const now = new Date()
    const diffMs = now - date
    const diffMins = Math.floor(diffMs / 60000)
    const diffHours = Math.floor(diffMs / 3600000)
    const diffDays = Math.floor(diffMs / 86400000)

    if (diffMins < 1) return 'Just now'
    if (diffMins < 60) return `${diffMins}m ago`
    if (diffHours < 24) return `${diffHours}h ago`
    return `${diffDays}d ago`
}

const getSeverityIndicator = (severity) => {
    const classes = {
        critical: 'bg-red-500',
        high: 'bg-orange-500',
        medium: 'bg-yellow-500',
        low: 'bg-blue-500',
        info: 'bg-gray-500'
    }
    return classes[severity] || 'bg-gray-500'
}

const getSeverityBadge = (severity) => {
    const classes = {
        critical: 'bg-red-100 text-red-800',
        high: 'bg-orange-100 text-orange-800',
        medium: 'bg-yellow-100 text-yellow-800',
        low: 'bg-blue-100 text-blue-800',
        info: 'bg-gray-100 text-gray-800'
    }
    return classes[severity] || 'bg-gray-100 text-gray-800'
}

// Data loading methods
const loadDashboardMetrics = async () => {
    loading.value = true
    try {
        // Load events metrics (last 24h)
        await loadEventsMetrics()
        
        // Load active alerts
        await loadAlertsMetrics()
        
        // Load detection rules
        await loadRulesMetrics()
        
        // Load recent alerts
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

const loadEventsMetrics = async () => {
    try {
        const endTime = new Date().toISOString()
        const startTime = new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString()
        const yesterdayStart = new Date(Date.now() - 48 * 60 * 60 * 1000).toISOString()
        const yesterdayEnd = new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString()

        // Query for today's events
        const todayQuery = {
            index: 'sentinelkit-*',
            size: 0,
            query: {
                range: {
                    '@timestamp': { gte: startTime, lte: endTime }
                }
            },
            aggs: {
                total_count: {
                    value_count: { field: '@timestamp' }
                }
            }
        }

        // Query for yesterday's events
        const yesterdayQuery = {
            ...todayQuery,
            query: {
                range: {
                    '@timestamp': { gte: yesterdayStart, lte: yesterdayEnd }
                }
            }
        }

        const [todayResponse, yesterdayResponse] = await Promise.all([
            fetch(`${BASE_URL}/elasticsearch/search`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(todayQuery)
            }),
            fetch(`${BASE_URL}/elasticsearch/search`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(yesterdayQuery)
            })
        ])

        if (todayResponse.ok && yesterdayResponse.ok) {
            const todayData = await todayResponse.json()
            const yesterdayData = await yesterdayResponse.json()
            
            const todayCount = todayData.data?.aggregations?.total_count?.value || 0
            const yesterdayCount = yesterdayData.data?.aggregations?.total_count?.value || 0
            
            dashboardData.totalEvents = todayCount
            dashboardData.eventsTrend = yesterdayCount > 0 
                ? Math.round(((todayCount - yesterdayCount) / yesterdayCount) * 100) 
                : 0
        }
    } catch (error) {
        console.error('Error loading events metrics:', error)
    }
}

const loadAlertsMetrics = async () => {
    try {
        const endTime = new Date().toISOString()
        const startTime = new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString()

        const response = await fetch(`${BASE_URL}/alerts?startDate=${startTime}&endDate=${endTime}&limit=1000`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        })

        if (response.ok) {
            const result = await response.json()
            const alerts = Array.isArray(result) ? result : (result.alerts || [])
            
            dashboardData.activeAlerts = alerts.length
            dashboardData.criticalAlerts = alerts.filter(alert => 
                alert.rule_version?.level === 'critical' || alert.sigmaRule?.level === 'critical'
            ).length
        }
    } catch (error) {
        console.error('Error loading alerts metrics:', error)
    }
}

const loadRulesMetrics = async () => {
    try {
        const response = await fetch(`${BASE_URL}/rules`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        })

        if (response.ok) {
            const rules = await response.json()
            dashboardData.totalRules = rules.length
            dashboardData.activeRules = rules.filter(rule => rule.enabled).length
        }
    } catch (error) {
        console.error('Error loading rules metrics:', error)
        dashboardData.activeRules = 112
        dashboardData.totalRules = 150
    }
}

const loadRecentAlerts = async () => {
    try {
        const endTime = new Date().toISOString()
        const startTime = new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString()

        const response = await fetch(`${BASE_URL}/alerts?startDate=${startTime}&endDate=${endTime}&limit=5`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        })

        if (response.ok) {
            const result = await response.json()
            const alerts = Array.isArray(result) ? result : (result.alerts || [])
            
            dashboardData.recentAlerts = alerts.slice(0, 5).map(alert => ({
                id: alert.id,
                title: alert.sigmaRule?.title || alert.rule?.name || 'Security Alert',
                description: alert.sigmaRule?.description || 'Security event detected',
                severity: alert.rule_version?.level || alert.sigmaRule?.level || 'medium',
                timestamp: alert.created_at || alert.event_timestamp
            }))
        }
    } catch (error) {
        console.error('Error loading recent alerts:', error)
        dashboardData.recentAlerts = []
    }
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
const {
    totalEvents,
    activeAlerts, 
    criticalAlerts,
    registeredAssets,
    activeRules,
    eventsTrend,
    recentAlerts
} = dashboardData
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