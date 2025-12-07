<template>
    <div class="datasources-dashboard bg-gray-50 min-h-screen overflow-auto">
        <div class="flex min-h-screen">
            <!-- Main Panel -->
            <div 
                :style="{ width: showEventDetails ? '70%' : '100%' }" 
                class="flex flex-col transition-all duration-300 min-h-full"
            >
                <!-- Header -->
                <div class="flex-shrink-0 bg-gray-50 p-8 pb-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h1 class="text-4xl font-extrabold text-gray-900 text-left">Datasources monitoring</h1>
                        <div class="flex items-center gap-4">
                            <!-- Time Range Selector -->
                            <select v-model="selectedTimeRange" @change="refreshData" class="p-3 border-0 shadow-md bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-1">
                                <option value="24h">Last 24 Hours</option>
                                <option value="7d">Last 7 Days</option>
                                <option value="30d">Last 30 Days</option>
                            </select>
                            <!-- Refresh Button -->
                            <button 
                                @click="refreshData"
                                :disabled="loading"
                                class="btn btn-primary flex items-center gap-2 px-4 py-3 shadow-md hover:shadow-lg transition-all duration-200"
                            >
                                <span class="icon-[material-symbols--refresh]" :class="{ 'animate-spin': loading }"></span>
                                Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Scrollable content -->
                <div class="flex-1">
                    <!-- Overview Stats -->
                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <div class="bg-white p-6 shadow-lg border-0">
                                <div v-if="loading" class="animate-pulse">
                                    <div class="h-4 bg-gray-200 w-24 mb-2"></div>
                                    <div class="h-8 bg-gray-200 w-16"></div>
                                </div>
                                <div v-else>
                                    <p class="text-sm text-gray-600 mb-1">Total Sources</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ datasources.length }}</p>
                                </div>
                            </div>
                            <div class="bg-white p-6 shadow-lg border-0">
                                <div v-if="loading" class="animate-pulse">
                                    <div class="h-4 bg-gray-200 w-24 mb-2"></div>
                                    <div class="h-8 bg-gray-200 w-20"></div>
                                </div>
                                <div v-else>
                                    <p class="text-sm text-gray-600 mb-1">Total Documents</p>
                                    <p class="text-2xl font-bold text-blue-600">{{ formatNumber(totalDocuments) }}</p>
                                </div>
                            </div>
                            <div class="bg-white p-6 shadow-lg border-0">
                                <div v-if="loading" class="animate-pulse">
                                    <div class="h-4 bg-gray-200 w-24 mb-2"></div>
                                    <div class="h-8 bg-gray-200 w-20"></div>
                                </div>
                                <div v-else>
                                    <p class="text-sm text-gray-600 mb-1">Total Size</p>
                                    <p class="text-2xl font-bold text-purple-600">{{ formatBytes(totalSize) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Ingestion Chart -->
                        <div class="bg-white shadow-lg border-0 mb-6">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h2 class="text-xl font-semibold text-gray-900">
                                    Ingestion volume over time
                                    <span v-if="selectedChartDatasource" class="text-sm font-normal text-gray-600 ml-2">
                                        - {{ selectedChartDatasource === 'all' ? 'All Sources' : selectedChartDatasource.name }}
                                    </span>
                                </h2>
                            </div>
                            <div class="p-6">
                                <IngestionChart
                                    :chart-data="chartData"
                                    :loading="chartLoading"
                                    :time-range="selectedTimeRange"
                                    :selected-datasource="selectedChartDatasource === 'all' ? 'All Sources' : selectedChartDatasource?.name"
                                />
                            </div>
                        </div>

                        <!-- Datasources List -->
                        <div class="bg-white rounded-lg shadow-sm border">
                            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <h2 class="text-xl font-semibold text-gray-900">Data Sources</h2>
                                <button 
                                    @click="selectDatasourceForChart('all')"
                                    class="btn text-sm px-4 py-2 rounded flex items-center gap-2"
                                    :class="selectedChartDatasource === 'all' ? 'btn-primary' : 'btn-soft'"
                                >
                                    <span class="icon-[material-symbols--select-all]"></span>
                                    {{ selectedChartDatasource === 'all' ? 'All Sources Selected' : 'Select All Sources' }}
                                </button>
                            </div>
                            <div class="overflow-x-auto">
                                <!-- Loading Skeleton -->
                                <div v-if="loading" class="animate-pulse">
                                    <table class="w-full">
                                        <thead class="bg-gray-50">
                                            <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documents</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Size</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Indices</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="i in 5" :key="i">
                                                <td class="px-6 py-4">
                                                    <div class="h-4 bg-gray-200 rounded w-32"></div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="h-4 bg-gray-200 rounded w-20"></div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="h-4 bg-gray-200 rounded w-16"></div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="h-4 bg-gray-200 rounded w-12"></div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="h-6 bg-gray-200 rounded w-20"></div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Actual Data -->
                                <table v-else class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Data Source
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Documents
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Size
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Indices
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr 
                                            v-for="datasource in datasources" 
                                            :key="datasource.name"
                                            class="hover:bg-gray-50 cursor-pointer"
                                            :class="{ 
                                                'bg-blue-50': selectedChartDatasource !== 'all' && selectedChartDatasource?.name === datasource.name
                                            }"
                                            @click="selectDatasourceForChart(datasource)"
                                        >
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <span class="icon-[material-symbols--database] w-5 h-5 text-gray-400 mr-3"></span>
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900">{{ datasource.name }}</div>
                                                        <div class="text-sm text-gray-500">{{ datasource.indices.length }} indices</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ formatNumber(datasource.totalDocuments) }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ formatBytes(datasource.totalSizeBytes) }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ datasource.indices.length }}
                                            </td>
                                            <td class="px-6 py-4 text-sm">
                                                <button 
                                                    @click.stop="selectDatasourceForChart(datasource)"
                                                    class="btn text-sm px-4 py-2 flex items-center gap-2 transition-all duration-200"
                                                    :class="selectedChartDatasource !== 'all' && selectedChartDatasource?.name === datasource.name ? 'btn-primary shadow-md' : 'btn-soft shadow-sm hover:shadow-md'"
                                                >
                                                    <span class="icon-[material-symbols--analytics]"></span>
                                                    {{ selectedChartDatasource !== 'all' && selectedChartDatasource?.name === datasource.name ? 'Selected' : 'Select' }}
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import IngestionChart from '../components/datasources/IngestionChart.vue'

const emit = defineEmits(['show-notification'])
const BASE_URL = import.meta.env.VITE_API_BASE_URL
const router = useRouter()
const route = useRoute()

// State
const loading = ref(false)
const chartLoading = ref(false)
const selectedTimeRange = ref('7d')
const selectedChartDatasource = ref(null)

// Data
const datasources = ref([])
const chartData = ref(null)

// Computed properties
const totalDocuments = computed(() => {
    return datasources.value.reduce((total, ds) => total + ds.totalDocuments, 0)
})

const totalSize = computed(() => {
    return datasources.value.reduce((total, ds) => total + ds.totalSizeBytes, 0)
})

// Utility functions
const formatNumber = (num) => {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M'
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K'
    }
    return num.toString()
}

const formatBytes = (bytes) => {
    if (bytes === 0) return '0 B'
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString()
}

const getEventLevelClass = (level) => {
    switch (level?.toLowerCase()) {
        case 'error':
            return 'bg-red-100 text-red-800'
        case 'warn':
        case 'warning':
            return 'bg-yellow-100 text-yellow-800'
        case 'info':
            return 'bg-blue-100 text-blue-800'
        case 'debug':
            return 'bg-gray-100 text-gray-800'
        default:
            return 'bg-gray-100 text-gray-800'
    }
}

// API functions
const loadDatasources = async () => {
    loading.value = true
    try {
        const response = await fetch(`${BASE_URL}/datasources`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        })

        if (response.ok) {
            const data = await response.json()
            datasources.value = data
        } else {
            throw new Error('Failed to load datasources')
        }
    } catch (error) {
        console.error('Error loading datasources:', error)
        emit('show-notification', {
            type: 'error',
            message: 'Failed to load data sources'
        })
    } finally {
        loading.value = false
    }
}

const loadIngestionChart = async () => {
    if (!selectedChartDatasource.value) return
    
    chartLoading.value = true
    
    try {
        if (selectedChartDatasource.value === 'all') {
            // Use aggregated mock data for all sources since backend doesn't support all endpoint yet
            chartData.value = {
                timeRange: selectedTimeRange.value,
                datasource: 'All Sources',
                data: generateMockChartData()
            }
        } else {
            const response = await fetch(`${BASE_URL}/datasources/${encodeURIComponent(selectedChartDatasource.value.name)}/ingestion-stats?timeRange=${selectedTimeRange.value}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                }
            })

            if (response.ok) {
                const data = await response.json()
                chartData.value = data
            } else {
                throw new Error('Failed to load ingestion stats')
            }
        }
    } catch (error) {
        console.error('Error loading ingestion stats:', error)
        // Use mock data for demonstration
        chartData.value = {
            timeRange: selectedTimeRange.value,
            datasource: selectedChartDatasource.value === 'all' ? 'All Sources' : selectedChartDatasource.value.name,
            data: generateMockChartData()
        }
    } finally {
        chartLoading.value = false
    }
}

// Event handlers
const refreshData = async () => {
    await loadDatasources()
    if (selectedChartDatasource.value) {
        await loadIngestionChart()
    }
}

const selectDatasourceForChart = async (datasource) => {
    selectedChartDatasource.value = datasource
    
    // Update URL
    const query = { ...route.query }
    if (datasource === 'all') {
        query.chart = 'all'
    } else {
        query.chart = datasource.name
    }
    router.replace({ query })
    
    await loadIngestionChart()
}

const generateMockChartData = () => {
    const intervals = selectedTimeRange.value === '24h' ? 24 : (selectedTimeRange.value === '7d' ? 7 : 30)
    const data = []
    
    const baseTime = new Date()
    const intervalSize = selectedTimeRange.value === '24h' ? 'hour' : 'day'
    
    for (let i = intervals; i >= 0; i--) {
        const time = new Date(baseTime)
        if (intervalSize === 'hour') {
            time.setHours(time.getHours() - i)
        } else {
            time.setDate(time.getDate() - i)
        }
        
        data.push({
            timestamp: time.toISOString(),
            count: Math.floor(Math.random() * 1000) + 100,
            sizeBytes: Math.floor(Math.random() * 10485760) + 1048576
        })
    }
    
    return data
}

// URL watchers
watch(() => route.query.chart, (newChart) => {
    if (newChart === 'all') {
        selectedChartDatasource.value = 'all'
    } else if (newChart && datasources.value.length > 0) {
        const found = datasources.value.find(ds => ds.name === newChart)
        if (found) {
            selectedChartDatasource.value = found
        }
    }
    if (selectedChartDatasource.value) {
        loadIngestionChart()
    }
})

// Lifecycle
onMounted(async () => {
    await loadDatasources()
    
    // Handle URL parameters
    const chartParam = route.query.chart
    if (chartParam === 'all') {
        selectedChartDatasource.value = 'all'
    } else if (chartParam && datasources.value.length > 0) {
        const found = datasources.value.find(ds => ds.name === chartParam)
        if (found) {
            selectedChartDatasource.value = found
        } else {
            selectedChartDatasource.value = 'all'
        }
    } else {
        // Default to All Sources
        selectedChartDatasource.value = 'all'
        router.replace({ query: { ...route.query, chart: 'all' } })
    }
    
    await loadIngestionChart()
})
</script>

<style scoped>
.datasources-dashboard {
    font-family: 'Inter', sans-serif;
}

.btn {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0; /* Angles droits */
    transition: color 0.15s, background-color 0.15s, box-shadow 0.15s;
    border: none;
}

.btn-primary {
    background-color: rgb(249 115 22);
    color: white;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.btn-primary:hover {
    background-color: rgb(234 88 12);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.btn-primary:disabled {
    opacity: 0.5;
    box-shadow: none;
}

.btn-soft {
    background-color: rgb(243 244 246);
    color: rgb(55 65 81);
    border: none;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.btn-soft:hover {
    background-color: rgb(229 231 235);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.btn-info {
    color: rgb(37 99 235);
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.btn-info:hover {
    color: rgb(29 78 216);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>