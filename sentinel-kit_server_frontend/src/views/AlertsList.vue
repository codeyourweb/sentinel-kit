<!--
/**
 * Alerts List View - Security Event and Alert Management Interface
 * 
 * This is a comprehensive view for managing and analyzing security alerts and events
 * from the Sentinel-Kit platform. It provides advanced filtering, searching, and
 * visualization capabilities for security analysts.
 * 
 * Features:
 * - Dual-mode display: Events view and Alerts-only view
 * - Real-time data refresh with configurable intervals
 * - Advanced search with regex and boolean operators
 * - Field-based filtering with dynamic facets
 * - Time range selection and custom date filtering
 * - Alert severity and status management
 * - Rule visualization with Monaco editor integration
 * - Resizable panels for optimal workflow
 * - Export functionality for analysis
 * 
 * Layout Components:
 * - Events Fields Panel: Dynamic field filtering and facets
 * - Main Data Table: Alerts/events with sorting and pagination
 * - Rule Viewer Panel: Monaco editor showing triggered rule content
 * - Filter Bar: Search, time range, and quick filters
 * 
 * Data Sources:
 * - Elasticsearch for event data
 * - Backend API for alert metadata and rules
 * - Real-time updates via WebSocket or polling
 */
-->

<template>
    <!-- Main Alerts Dashboard Container -->
    <div class="alerts-dashboard bg-gray-50 h-screen overflow-hidden">
        <div class="flex h-full">
            <div 
                :style="{ width: showRuleViewer ? `${leftPanelWidth}%` : '100%' }" 
                class="flex flex-col transition-all duration-300"
            >
                <!-- title with toggle -->
                <div class="flex-shrink-0 bg-gray-50 p-6 pb-3 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h1 class="text-4xl font-extrabold text-gray-900 text-left">{{ pageTitle }}</h1>
                        <!-- View Toggle Slider -->
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-gray-700">Events</span>
                            <label class="inline-flex items-center cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    v-model="showAlertsOnly" 
                                    @change="refreshData"
                                    class="sr-only peer"
                                />
                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                            </label>
                            <span class="text-sm font-medium text-gray-700">Alerts Only</span>
                        </div>
                    </div>
                </div>

                <!-- Scrollable content of the main area -->
                <div class="flex-1 h-full overflow-hidden" 
                     :style="{ 
                         display: 'grid', 
                         gridTemplateColumns: showAlertsOnly ? '1fr' : (fieldsCollapsed ? '64px 1fr' : '280px 1fr'),
                         transition: 'grid-template-columns 0.3s ease'
                     }">
                    <!-- Fixed Events Fields Panel (only in events mode, positioned after filters) -->
                    <div v-if="!showAlertsOnly" 
                         class="border-r border-gray-200 bg-white overflow-hidden">
                        <div class="h-full flex flex-col w-full overflow-hidden">
                            
                            <!-- Fields Panel Content -->
                            <div class="flex-1">
                                <EventsFieldsPanel
                                    :events="events"
                                    :loading="eventsLoading"
                                    :collapsed="fieldsCollapsed"
                                    @filters-changed="onFieldFiltersChanged"
                                    @toggle-collapse="fieldsCollapsed = !fieldsCollapsed"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Main content area -->
                    <div class="flex flex-col overflow-hidden">
                        <!-- Sticky Filters Section Only -->
                        <div class="sticky top-0 z-10 bg-white shadow-sm">
                            <div class="m-6 mb-0">
                                <!-- Dashboard Filters Component -->
                                <div class="p-6 pb-4">
                                    <DashboardFilters
                                        v-model:model-start-date="startDate"
                                        v-model:model-end-date="endDate"
                                        v-model:model-show-alerts-only="showAlertsOnly"
                                        v-model:model-alert-filter="alertFilter"
                                        :search-placeholder="searchPlaceholder"
                                        :loading="loading"
                                        :current-page="currentPage"
                                        :total-pages="totalPages"
                                        :total-items="totalItems"
                                        :page-size="pageSize"
                                        :has-more-pages="hasMorePages"
                                        @refresh="refreshData"
                                        @filter-change="filterAlerts"
                                        @page-change="handlePageChange"
                                        :hide-pagination="false"
                                        :hide-toggle="true"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Content Section -->
                        <div class="flex-1 overflow-y-auto">
                            <!-- Chart Section -->
                            <div class="bg-white m-6 mb-4">
                                <EventChart
                                    :chart-data="chartData"
                                    :chart-options="chartOptions"
                                    :chart-key="chartKey"
                                    :loading="loading"
                                    :show-alerts-only="showAlertsOnly"
                                    :total-count="totalCount"
                                    :current-interval="currentIntervalText"
                                    :start-date="startDate"
                                    :end-date="endDate"
                                    :custom-interval="customInterval"
                                    v-model:collapsed="chartCollapsed"
                                    @date-range-selected="onDateRangeSelected"
                                    @interval-changed="onIntervalChanged"
                                    :hide-title="true"
                                />
                            </div>
                            
                            <!-- Alerts Table Component (only in alerts mode) -->
                            <div v-if="showAlertsOnly" class="mx-6 mb-6">
                                <AlertsTable
                                    :alerts="filteredAlerts"
                                    :paginated-alerts="paginatedAlerts"
                                    :alerts-loading="alertsLoading"
                                    :expanded-alerts="expandedAlerts"
                                    :alert-details="alertDetails"
                                    :sort-field="alertSortField"
                                    :sort-direction="alertSortDirection"
                                    @toggle-details="toggleAlertDetails"
                                    @view-rule="viewRule"
                                    @sort="handleAlertSort"
                                />
                            </div>

                            <!-- Events Table (only in events mode, without fields panel) -->
                            <div v-if="!showAlertsOnly" class="mx-6 mb-6">
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <EventsTableSimplified
                                        :events="events"
                                        :events-loading="eventsLoading"
                                        :filters="fieldFilters"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Divider for resizing -->
            <div 
                v-if="showRuleViewer" 
                @mousedown="startResize"
                class="w-1 bg-gray-300 hover:bg-blue-500 cursor-col-resize transition-colors duration-200 flex-shrink-0"
                :class="{ 'bg-blue-500': isResizing }"
            ></div>

            <!-- Monaco Editor Panel - Fixed and non-scrollable -->
            <div 
                v-if="showRuleViewer" 
                :style="{ width: `${getRightPanelWidth}%` }"
                class="bg-white flex flex-col h-full"
            >
                <!-- Header with close button - Fixed -->
                <div class="p-4 border-b border-gray-200 flex justify-between items-center flex-shrink-0">
                    <h3 class="text-lg font-semibold text-gray-800">Rule details</h3>
                    <a 
                        @click="closeRuleViewer"
                        class="btn btn-soft btn-info"
                        href="#"
                    >
                        <span class="icon-[material-symbols--close-rounded]"></span>
                    </a>
                </div>

                <!-- Information about the rule - Fixed -->
                <div v-if="selectedRule" class="p-4 border-b border-gray-200 flex-shrink-0">
                    <h4 class="font-medium text-gray-900 mb-2 text-left">
                        <RouterLink class="btn btn-soft btn-info mr-2" :to="{ name: 'RuleEdit', params: { id: selectedRule.id } }">
                            <span class="icon-[material-symbols--edit-document-outline-rounded] bg-orange-400 text-orange-400"></span>
                        </RouterLink>
                        {{ selectedRule.title }}                    
                    </h4>
                    <p class="text-sm text-gray-600 mb-2 text-left">{{ selectedRule.description }}</p>
                    <div class="flex items-center space-x-4 text-xs">
                        <div class="flex items-center space-x-2">
                            <span class="text-gray-500">Level:</span>
                            <span :class="getSeverityClass(selectedRule.level)" class="px-2 py-1 text-xs font-semibold rounded-full">
                                {{ selectedRule.level || 'unknown' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Monaco Editor Content - Takes up the remaining space -->
                <div class="flex-1 flex flex-col min-h-0">
                    <!-- Loading area -->
                    <div v-if="ruleLoading" class="flex-1 flex items-center justify-center">
                        <div class="text-center">
                            <span class="icon-[svg-spinners--ring-resize] w-8 h-8 text-orange-600 animate-spin mb-2"></span>
                            <p class="text-gray-600">Loading rule content...</p>
                        </div>
                    </div>

                    <!-- Loading error -->
                    <div v-else-if="ruleError" class="flex-1 flex items-center justify-center p-4">
                        <div class="text-center text-red-600">
                            <span class="icon-[material-symbols--error] w-8 h-8 mb-2"></span>
                            <p>{{ ruleError }}</p>
                        </div>
                    </div>

                    <!-- Monaco Editor - Takes up the remaining space -->
                    <div v-else-if="ruleContent" class="flex-1">
                        <RuleEditor
                            v-model="ruleContent"
                            :read-only="true"
                            class="h-full w-full"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardFilters from '../components/dashboard/DashboardFilters.vue'
import EventChart from '../components/dashboard/EventChart.vue'
import AlertsTable from '../components/dashboard/AlertsTable.vue'
import EventsTable from '../components/dashboard/EventsTable.vue'
import EventsFieldsPanel from '../components/dashboard/EventsFieldsPanel.vue'
import EventsTableSimplified from '../components/dashboard/EventsTableSimplified.vue'
import RuleEditor from '../components/RuleEditor.vue'
import { useChartTimeUtils } from '../composables/useChartTimeUtils'
import { useAlertUtils } from '../composables/useAlertUtils'

// Router
const route = useRoute()
const router = useRouter()
const isInitialLoad = ref(true)

// Composables
const { getTimeInterval, createChartOptions, getCurrentInterval, getAvailableIntervals } = useChartTimeUtils()
const { getSeverityClass } = useAlertUtils()

// Reactive data
const loading = ref(false)
const alertsLoading = ref(false)
const eventsLoading = ref(false)
const showAlertsOnly = ref(false)
const alertFilter = ref('')
const totalCount = ref(0)
const totalAlertsCount = ref(0)
const chartKey = ref(0)
const currentPage = ref(1)
const pageSize = ref(100)
const totalAlerts = ref(0)
const expandedAlerts = ref(new Set())
const alertDetails = reactive({})
const chartCollapsed = ref(false)
const customInterval = ref('')
const fieldFilters = ref([])
const fieldsCollapsed = ref(false)

// Events data
const events = ref([])
const totalEvents = ref(0)

// Sorting state
const alertSortField = ref('alertTime')
const alertSortDirection = ref('desc')
const eventSortField = ref('@timestamp')
const eventSortDirection = ref('desc')

// Variables for the Monaco Editor panel
const showRuleViewer = ref(false)
const selectedRule = ref(null)
const ruleContent = ref('')
const ruleLoading = ref(false)
const ruleError = ref(null)

// Variables for resizing
const isResizing = ref(false)
const leftPanelWidth = ref(75) // Percentage for the left panel (75% by default)

// Variables for search debouncing
const searchTimeout = ref(null)

const BASE_URL = import.meta.env.VITE_API_BASE_URL

// Date range (default: last 7 days)
const endDate = ref(new Date().toISOString().slice(0, 16))
const startDate = ref(new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 16))

// Data
const alerts = ref([])
const chartData = ref({
    labels: [],
    datasets: [{
        label: 'Events',
        data: [],
        backgroundColor: 'rgba(32, 105, 154, 0.7)',
        borderColor: 'rgb(32, 105, 154)',
        borderWidth: 1,
        borderRadius: 4,
        borderSkipped: false
    }]
})

// Chart options
const chartOptions = ref({})

// Computed
const currentIntervalText = computed(() => {
    return getCurrentInterval(startDate.value, endDate.value, customInterval.value)
})

const pageTitle = computed(() => {
    return showAlertsOnly.value ? 'Alerts' : 'Security Events'
})

const searchPlaceholder = computed(() => {
    return showAlertsOnly.value ? 'Search alerts' : 'Search events using Kibana syntax (e.g. log:"queue is", host:server01, status:failed)'
})

const filteredAlerts = computed(() => {
    // Filtering is now done server-side via Elasticsearch
    const sorted = [...alerts.value]
    return sortArray(sorted, alertSortField.value, alertSortDirection.value, 'alert')
})

const sortedEvents = computed(() => {
    const sorted = [...events.value]
    return sortArray(sorted, eventSortField.value, eventSortDirection.value, 'event')
})

const totalItems = computed(() => {
    return showAlertsOnly.value ? totalAlerts.value : totalEvents.value
})

const paginatedAlerts = computed(() => {
    // Use direct server results since server handles pagination
    return filteredAlerts.value
})

const totalPages = computed(() => {
    return Math.ceil(totalItems.value / pageSize.value)
})

const hasMorePages = computed(() => {
    // Display pagination if there is data (even on a single page) or if there are multiple pages
    return totalItems.value > 0
})

// Methods
const refreshData = async () => {
    if (!isInitialLoad.value) {
        updateURL()
    }
    
    if (showAlertsOnly.value) {
        await Promise.all([
            loadChartData(),
            loadAlerts()
        ])
    } else {
        await Promise.all([
            loadChartData(),
            loadEvents()
        ])
    }
}

const loadEvents = async () => {
    eventsLoading.value = true
    try {
        const startTime = new Date(startDate.value).toISOString()
        const endTime = new Date(endDate.value).toISOString()
        
        const query = {
            index: 'sentinelkit-*',
            size: pageSize.value,
            from: (currentPage.value - 1) * pageSize.value,
            sort: [
                { '@timestamp': { order: 'desc' } }
            ],
            query: {
                bool: {
                    must: [
                        {
                            range: {
                                '@timestamp': {
                                    gte: startTime,
                                    lte: endTime
                                }
                            }
                        }
                    ]
                }
            }
        }

        // Add search filter if provided
        if (alertFilter.value && alertFilter.value.trim()) {
            // Use query_string with optimized configuration for boolean operators
            query.query.bool.must.push({
                query_string: {
                    query: alertFilter.value.trim(),
                    fields: ['*'],
                    default_operator: 'AND',
                    analyze_wildcard: true,
                    allow_leading_wildcard: true,
                    enable_position_increments: true,
                    escape: false,
                    phrase_slop: 0,
                    tie_breaker: 0.0,
                    time_zone: 'UTC',
                    boost: 1.0
                }
            })
        }

        const response = await fetch(`${BASE_URL}/elasticsearch/search`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(query)
        })

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        const result = await response.json()
        
        if (result.success && result.data && result.data.hits) {
            events.value = result.data.hits.hits
            // Get total count from Elasticsearch response
            totalEvents.value = result.data.hits.total?.value || result.data.hits.total || events.value.length
        } else {
            events.value = []
            totalEvents.value = 0
        }
    } catch (error) {
        console.error('Error loading events:', error)
        events.value = []
        totalEvents.value = 0
    } finally {
        eventsLoading.value = false
    }
}

const loadChartData = async () => {
    loading.value = true
    try {
        const startTime = new Date(startDate.value).toISOString()
        const endTime = new Date(endDate.value).toISOString()
        
        const timeConfig = getTimeInterval(startTime, endTime, customInterval.value)
        
        chartOptions.value = createChartOptions(startDate.value, endDate.value, customInterval.value)
        
        const query = {
            index: showAlertsOnly.value ? 'elastalert_status' : 'sentinelkit-*',
            size: 0,
            query: {
                bool: {
                    must: [
                        {
                            range: {
                                '@timestamp': {
                                    gte: startTime,
                                    lte: endTime
                                }
                            }
                        }
                    ]
                }
            },
            aggs: {
                events_over_time: {
                    date_histogram: {
                        field: '@timestamp',
                        [timeConfig.intervalType]: timeConfig.interval,
                        time_zone: 'UTC',
                        min_doc_count: 0,
                        extended_bounds: {
                            min: startTime,
                            max: endTime
                        }
                    }
                },
                total_count: {
                    value_count: {
                        field: '@timestamp'
                    }
                }
            }
        }

        if (showAlertsOnly.value && alertFilter.value.trim()) {
            query.query.bool.must.push({
                wildcard: {
                    'rule_name.keyword': `*${alertFilter.value.trim()}*`
                }
            })
        }

        const response = await fetch(`${BASE_URL}/elasticsearch/search`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(query)
        })

        if (!response.ok) {
            const errorText = await response.text()
            throw new Error(`HTTP error! status: ${response.status} - ${errorText}`)
        }

        const result = await response.json()
        
        if (result.success && result.data) {
            const buckets = result.data.aggregations?.events_over_time?.buckets || []
            totalCount.value = result.data.aggregations?.total_count?.value || 0
            
            if (showAlertsOnly.value) {
                totalAlertsCount.value = totalCount.value
                totalAlerts.value = totalCount.value
            }
            
            chartData.value = {
                labels: buckets.map(bucket => bucket.key),
                datasets: [{
                    label: showAlertsOnly.value ? 'Alerts' : 'Events',
                    data: buckets.map(bucket => bucket.doc_count),
                    backgroundColor: showAlertsOnly.value ? 'rgba(249, 115, 22, 0.7)' : 'rgba(32, 105, 154, 0.7)',
                    borderColor: showAlertsOnly.value ? 'rgb(249, 115, 22)' : 'rgb(32, 105, 154)',
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false
                }]
            }
            
            chartKey.value++
        } else {
            throw new Error(result.error || 'Failed to fetch chart data')
        }
    } catch (error) {
        console.error('Error loading chart data:', error)
        totalCount.value = 0
        chartData.value = {
            labels: [],
            datasets: [{
                label: showAlertsOnly.value ? 'Alerts' : 'Events',
                data: [],
                backgroundColor: showAlertsOnly.value ? 'rgba(249, 115, 22, 0.7)' : 'rgba(32, 105, 154, 0.7)',
                borderColor: showAlertsOnly.value ? 'rgb(249, 115, 22)' : 'rgb(32, 105, 154)',
                borderWidth: 1,
                borderRadius: 4,
                borderSkipped: false
            }]
        }
    } finally {
        loading.value = false
    }
}

const loadAlerts = async () => {
    alertsLoading.value = true
    try {
        const startTime = new Date(startDate.value).toISOString()
        const endTime = new Date(endDate.value).toISOString()
        
        const params = new URLSearchParams({
            startDate: startTime,
            endDate: endTime,
            page: currentPage.value.toString(),
            limit: pageSize.value.toString()
        })
        
        if (alertFilter.value.trim()) {
            params.append('filter', alertFilter.value.trim())
        }

        const response = await fetch(`${BASE_URL}/alerts?${params.toString()}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                'Content-Type': 'application/json'
            }
        })

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        const result = await response.json()
        
        const alertsArray = Array.isArray(result) ? result : (result.alerts || [])
        
        if (result.pagination) {
            totalAlerts.value = result.pagination.total || alertsArray.length
        } else {
            totalAlerts.value = alertsArray.length
        }
        
        const mappedAlerts = alertsArray.map(alert => ({
            ...alert,
            sigmaRule: {
                ...alert.rule,
                level: alert.rule_version?.level
            },
            alertTime: alert.created_at || alert.event_timestamp
        }))
        
        alerts.value = mappedAlerts.sort((a, b) => new Date(b.alertTime) - new Date(a.alertTime))
        
    } catch (error) {
        console.error('Error loading alerts:', error)
        alerts.value = []
        totalAlerts.value = 0
        totalAlertsCount.value = 0
    } finally {
        alertsLoading.value = false
    }
}

// Sorting methods
const sortArray = (array, field, direction, type) => {
    return array.sort((a, b) => {
        let aVal, bVal
        
        if (type === 'alert') {
            switch (field) {
                case 'title':
                    aVal = a.sigmaRule?.title || ''
                    bVal = b.sigmaRule?.title || ''
                    break
                case 'level':
                    aVal = a.sigmaRule?.level || 'unknown'
                    bVal = b.sigmaRule?.level || 'unknown'
                    break
                case 'alertTime':
                    aVal = new Date(a.alertTime)
                    bVal = new Date(b.alertTime)
                    break
                default:
                    aVal = a[field] || ''
                    bVal = b[field] || ''
            }
        } else {
            // Event sorting
            switch (field) {
                case '@timestamp':
                case 'timestamp':
                    aVal = new Date(a._source['@timestamp'] || a._source.timestamp)
                    bVal = new Date(b._source['@timestamp'] || b._source.timestamp)
                    break
                case 'index':
                    aVal = a._index || ''
                    bVal = b._index || ''
                    break
                default:
                    aVal = a._source[field] || ''
                    bVal = b._source[field] || ''
            }
        }
        
        if (aVal instanceof Date && bVal instanceof Date) {
            return direction === 'asc' ? aVal - bVal : bVal - aVal
        }
        
        if (typeof aVal === 'string' && typeof bVal === 'string') {
            return direction === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal)
        }
        
        return direction === 'asc' ? aVal - bVal : bVal - aVal
    })
}

const handleAlertSort = (field) => {
    if (alertSortField.value === field) {
        alertSortDirection.value = alertSortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
        alertSortField.value = field
        alertSortDirection.value = 'asc'
    }
}

const handleEventSort = (field) => {
    if (eventSortField.value === field) {
        eventSortDirection.value = eventSortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
        eventSortField.value = field
        eventSortDirection.value = 'asc'
    }
}

const handlePageChange = (page) => {
    currentPage.value = page
}

const filterAlerts = () => {
    currentPage.value = 1
    updateURL()
    
    if (showAlertsOnly.value) {
        // For alerts, immediate search
        loadAlerts()
        loadChartData()
    } else {
        // For events, debounced search to avoid too many Elasticsearch queries
        if (searchTimeout.value) {
            clearTimeout(searchTimeout.value)
        }
        
        searchTimeout.value = setTimeout(() => {
            loadEvents()
            loadChartData()
        }, 500) // 500ms delay
    }
}

const toggleAlertDetails = async (alertId) => {
    if (expandedAlerts.value.has(alertId)) {
        expandedAlerts.value.delete(alertId)
        return
    }

    expandedAlerts.value.add(alertId)
    
    if (!alertDetails[alertId]) {
        alertDetails[alertId] = { loading: true, data: null, error: null }
        
        try {
            const alert = alerts.value.find(a => a.id === alertId)
            if (!alert?.elastic_document_id) {
                throw new Error('No elastic document ID found for this alert')
            }

            const query = {
                index: 'sentinelkit-*',
                size: 1,
                query: {
                    bool: {
                        must: [
                            {
                                ids: {
                                    values: [alert.elastic_document_id]
                                }
                            }
                        ]
                    }
                }
            }

            const response = await fetch(`${BASE_URL}/elasticsearch/search`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(query)
            })

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`)
            }

            const result = await response.json()
            
            if (result.success && result.data && result.data.hits && result.data.hits.hits.length > 0) {
                const document = result.data.hits.hits[0]
                const sourceData = document._source || {}
                
                alertDetails[alertId] = { 
                    loading: false, 
                    data: sourceData,
                    error: null 
                }
            } else {
                throw new Error('Document not found in Elasticsearch')
            }
        } catch (error) {
            console.error('Error loading alert details from Elasticsearch:', error)
            alertDetails[alertId] = { 
                loading: false, 
                data: null,
                error: error.message 
            }
        }
    }
}

const onDateRangeSelected = (dateRange) => {
    startDate.value = dateRange.startDate
    endDate.value = dateRange.endDate
    refreshData()
}

const onIntervalChanged = (interval) => {
    customInterval.value = interval
    updateURL()
    loadChartData()
}

const onFieldFiltersChanged = (filters) => {
    fieldFilters.value = filters
}

const getVisiblePages = () => {
    const pages = []
    const total = totalPages.value
    const current = currentPage.value
    
    if (total <= 7) {
        for (let i = 1; i <= total; i++) {
            pages.push(i)
        }
    } else {
        pages.push(1)
        
        if (current > 4) {
            pages.push('...')
        }
        
        const start = Math.max(2, current - 1)
        const end = Math.min(total - 1, current + 1)
        
        for (let i = start; i <= end; i++) {
            if (i !== 1 && i !== total) {
                pages.push(i)
            }
        }
        
        if (current < total - 3) {
            pages.push('...')
        }
        
        if (total > 1) {
            pages.push(total)
        }
    }
    
    return pages
}

const viewRule = async (alert) => {
    if (!alert.rule?.id) {
        ruleError.value = 'No rule information available'
        return
    }

    selectedRule.value = {
        id: alert.sigmaRule?.id || '',
        title: alert.sigmaRule?.title || 'Unknown Rule',
        description: alert.sigmaRule?.description || 'No description',
        level: alert.sigmaRule?.level || 'unknown',
        version: alert.rule_version?.id || 'N/A'
    }
    
    showRuleViewer.value = true
    ruleLoading.value = true
    ruleError.value = null
    ruleContent.value = ''

    try {
        const response = await fetch(`${BASE_URL}/rules/sigma/${alert.rule.id}/details`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                'Content-Type': 'application/json'
            }
        })

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        const result = await response.json()
        
        if (result && result.versions && result.versions.length > 0) {
            let targetVersion = null
            if (alert.rule_version?.id) {
                targetVersion = result.versions.find(v => v.id === alert.rule_version.id)
            }
            
            if (!targetVersion) {
                targetVersion = result.versions[0]
            }
            
            if (targetVersion && targetVersion.content) {
                ruleContent.value = targetVersion.content.replace(/\\n/g, '\n')
            } else {
                throw new Error('No content found for this rule version')
            }
        } else {
            throw new Error('No versions found for this rule')
        }
    } catch (error) {
        console.error('Error loading rule content:', error)
        ruleError.value = `Failed to load rule content: ${error.message}`
    } finally {
        ruleLoading.value = false
    }
}

const closeRuleViewer = () => {
    showRuleViewer.value = false
    selectedRule.value = null
    ruleContent.value = ''
    ruleError.value = null
}

const startResize = (event) => {
    isResizing.value = true
    event.preventDefault()
    
    const handleMouseMove = (e) => {
        if (isResizing.value) {
            const container = document.querySelector('.alerts-dashboard')
            if (container) {
                const containerRect = container.getBoundingClientRect()
                const containerWidth = containerRect.width
                const newLeftWidth = ((e.clientX - containerRect.left) / containerWidth) * 100
                
                leftPanelWidth.value = Math.max(30, Math.min(85, newLeftWidth))
            }
        }
    }
    
    const handleMouseUp = () => {
        isResizing.value = false
        document.removeEventListener('mousemove', handleMouseMove)
        document.removeEventListener('mouseup', handleMouseUp)
        document.body.style.cursor = ''
        document.body.style.userSelect = ''
    }
    
    document.body.style.cursor = 'col-resize'
    document.body.style.userSelect = 'none'
    document.addEventListener('mousemove', handleMouseMove)
    document.addEventListener('mouseup', handleMouseUp)
}

const getRightPanelWidth = computed(() => {
    return 100 - leftPanelWidth.value
})

// URL state management
const updateURL = () => {
    const query = {
        startDate: startDate.value,
        endDate: endDate.value,
        showAlertsOnly: showAlertsOnly.value.toString(),
        alertFilter: alertFilter.value || undefined,
        currentPage: currentPage.value > 1 ? currentPage.value.toString() : undefined,
        chartCollapsed: chartCollapsed.value.toString(),
        customInterval: customInterval.value || undefined
    }
    
    Object.keys(query).forEach(key => {
        if (query[key] === undefined || query[key] === 'undefined') {
            delete query[key]
        }
    })
    
    const currentQuery = route.query
    if (JSON.stringify(query) !== JSON.stringify(currentQuery)) {
        router.push({ query })
    }
}

const loadFromURL = () => {
    const query = route.query
    
    if (query.startDate) {
        startDate.value = query.startDate
    }
    if (query.endDate) {
        endDate.value = query.endDate
    }
    if (query.showAlertsOnly !== undefined) {
        showAlertsOnly.value = query.showAlertsOnly === 'true'
    }
    if (query.alertFilter) {
        alertFilter.value = query.alertFilter
    }
    if (query.currentPage) {
        currentPage.value = parseInt(query.currentPage) || 1
    }
    if (query.chartCollapsed !== undefined) {
        chartCollapsed.value = query.chartCollapsed === 'true'
    }
    if (query.customInterval !== undefined) {
        customInterval.value = query.customInterval || ''
    }
}

// Watchers
watch(showAlertsOnly, () => {
    // Hide rule viewer when switching to events mode
    if (!showAlertsOnly.value && showRuleViewer.value) {
        showRuleViewer.value = false
    }
    refreshData()
})

watch(alertFilter, () => {
    currentPage.value = 1
    
    if (showAlertsOnly.value) {
        // Immediate search for alerts
        loadAlerts()
        loadChartData()
    } else {
        // Debounced search for events
        if (searchTimeout.value) {
            clearTimeout(searchTimeout.value)
        }
        
        searchTimeout.value = setTimeout(() => {
            loadEvents()
            loadChartData()
        }, 500) // 500ms delay
    }
})

watch([startDate, endDate], () => {
    currentPage.value = 1
    updateURL()
    if (showAlertsOnly.value) {
        loadAlerts()
    } else {
        loadEvents()
    }
})

watch(() => route.query, (newQuery, oldQuery) => {
    if (isInitialLoad.value) return
    
    if (JSON.stringify(newQuery) !== JSON.stringify(oldQuery)) {
        loadFromURL()
        nextTick(() => {
            loadChartData()
            if (showAlertsOnly.value) {
                loadAlerts()
            } else {
                loadEvents()
            }
        })
    }
}, { deep: true })

watch(currentPage, () => {
    updateURL()
    if (showAlertsOnly.value) {
        loadAlerts()
    } else {
        loadEvents()
    }
})

watch(chartCollapsed, () => {
    updateURL()
})

watch(customInterval, () => {
    updateURL()
})

// Lifecycle
onMounted(() => {
    loadFromURL()
    
    if (!startDate.value) {
        startDate.value = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 16)
    }
    if (!endDate.value) {
        endDate.value = new Date().toISOString().slice(0, 16)
    }
    
    chartOptions.value = createChartOptions(startDate.value, endDate.value, customInterval.value)
    
    refreshData().then(() => {
        isInitialLoad.value = false
    })
})
</script>

<style scoped>
.alerts-dashboard {
    font-family: 'Inter', sans-serif;
}

.transition-all {
    transition: all 0.3s ease;
}
</style>
