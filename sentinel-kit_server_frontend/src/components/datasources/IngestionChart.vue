<template>
    <div class="ingestion-chart">
        <!-- Chart Controls -->
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="text-lg font-medium text-gray-900">
                    {{ selectedDatasource ? `${selectedDatasource} Ingestion` : 'Overall Ingestion Volume' }}
                </h3>
                <p class="text-sm text-gray-600">
                    {{ getTimeRangeDescription() }}
                </p>
            </div>
        </div>

        <!-- Chart Container -->
        <div class="relative">
            <!-- Loading State -->
            <div v-if="loading" class="animate-pulse h-64 bg-gray-100 rounded-lg flex flex-col justify-center items-center space-y-4">
                <!-- Chart skeleton -->
                <div class="w-full h-48 bg-gray-200 rounded flex items-end justify-around px-4">
                    <!-- Bars skeleton -->
                    <div class="w-8 bg-gray-300 rounded-t" style="height: 60%"></div>
                    <div class="w-8 bg-gray-300 rounded-t" style="height: 80%"></div>
                    <div class="w-8 bg-gray-300 rounded-t" style="height: 40%"></div>
                    <div class="w-8 bg-gray-300 rounded-t" style="height: 90%"></div>
                    <div class="w-8 bg-gray-300 rounded-t" style="height: 70%"></div>
                    <div class="w-8 bg-gray-300 rounded-t" style="height: 50%"></div>
                    <div class="w-8 bg-gray-300 rounded-t" style="height: 85%"></div>
                    <div class="w-8 bg-gray-300 rounded-t" style="height: 65%"></div>
                </div>
                <!-- X-axis skeleton -->
                <div class="w-full flex justify-around px-4">
                    <div v-for="i in 8" :key="i" class="h-3 bg-gray-300 rounded w-12"></div>
                </div>
            </div>

            <!-- Chart Canvas -->
            <div v-else-if="chartData?.data?.length" class="relative">
                <div class="w-full h-64 relative">
                    <canvas 
                        ref="chartCanvas" 
                        class="absolute inset-0 w-full h-full"
                        :key="`chart-${selectedDatasource}-${timeRange}-${chartData?.data?.length}`"
                        width="800"
                        height="256"
                    ></canvas>
                </div>
            </div>

            <!-- No Data State -->
            <div v-else class="h-64 flex items-center justify-center bg-gray-50 shadow-sm border-0">
                <div class="text-center text-gray-500">
                    <span class="icon-[material-symbols--chart-data] w-12 h-12 mb-2"></span>
                    <p>No ingestion data available</p>
                    <p class="text-sm">Select a data source to view ingestion statistics</p>
                </div>
            </div>
        </div>

        <!-- Chart Legend/Stats -->
        <div v-if="chartData?.data?.length" class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-blue-50 p-4 shadow-md border-0">
                <div class="text-sm font-medium text-blue-800">Total Events</div>
                <div class="text-xl font-bold text-blue-900">{{ formatNumber(totalEvents) }}</div>
            </div>
            <div class="bg-green-50 p-4 shadow-md border-0">
                <div class="text-sm font-medium text-green-800">Average per {{ timeRange === '24h' ? 'Hour' : 'Day' }}</div>
                <div class="text-xl font-bold text-green-900">{{ formatNumber(averageEvents) }}</div>
            </div>
            <div class="bg-purple-50 p-4 shadow-md border-0">
                <div class="text-sm font-medium text-purple-800">Total Size</div>
                <div class="text-xl font-bold text-purple-900">{{ formatBytes(totalSize) }}</div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, computed, nextTick, onBeforeUnmount } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
    chartData: Object,
    loading: Boolean,
    timeRange: String,
    selectedDatasource: String
})

const chartCanvas = ref(null)
const chartInstance = ref(null)

// Computed properties
const totalEvents = computed(() => {
    if (!props.chartData?.data) return 0
    return props.chartData.data.reduce((sum, item) => sum + item.count, 0)
})

const averageEvents = computed(() => {
    if (!props.chartData?.data?.length) return 0
    return Math.round(totalEvents.value / props.chartData.data.length)
})

const totalSize = computed(() => {
    if (!props.chartData?.data) return 0
    return props.chartData.data.reduce((sum, item) => sum + item.sizeBytes, 0)
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

const getTimeRangeDescription = () => {
    switch (props.timeRange) {
        case '24h':
            return 'Ingestion volume over the last 24 hours'
        case '7d':
            return 'Ingestion volume over the last 7 days'
        case '30d':
            return 'Ingestion volume over the last 30 days'
        default:
            return 'Ingestion volume'
    }
}

const destroyChart = () => {
    try {
        if (chartInstance.value) {
            // Stop any ongoing animations
            if (typeof chartInstance.value.stop === 'function') {
                chartInstance.value.stop()
            }
            
            // Destroy the chart instance
            if (typeof chartInstance.value.destroy === 'function') {
                chartInstance.value.destroy()
            }
            
            chartInstance.value = null
        }
    } catch (error) {
        console.warn('Error destroying chart:', error)
        chartInstance.value = null
    }
}

const createChart = async () => {
    try {
        // Destroy existing chart first with safety check
        destroyChart()
        
        // Wait for DOM updates
        await nextTick()
        
        // Multiple verification layers
        if (!chartCanvas.value) {
            console.warn('Canvas ref not available for chart creation')
            return
        }
        
        // Verify data availability
        if (!props.chartData?.data?.length) {
            console.warn('No chart data available for chart creation')
            return
        }

        // Check if canvas is properly attached to DOM
        if (!document.contains(chartCanvas.value)) {
            console.warn('Canvas element not attached to document')
            return
        }
        
        // Check if canvas is still mounted in DOM and visible
        if (!chartCanvas.value.offsetParent && chartCanvas.value.offsetHeight === 0) {
            console.warn('Canvas element not visible or mounted')
            return
        }
        
        // Verify canvas is properly mounted and has dimensions
        if (!chartCanvas.value.clientWidth || !chartCanvas.value.clientHeight) {
            console.warn('Canvas has no dimensions, waiting...')
            return
        }
        
        // Verify canvas has ownerDocument (critical for Chart.js)
        if (!chartCanvas.value.ownerDocument) {
            console.warn('Canvas element has no ownerDocument')
            return
        }
        
        // Get context with extended validation
        const ctx = chartCanvas.value.getContext('2d')
        if (!ctx) {
            console.error('Failed to get 2D context from canvas')
            return
        }
        
        // Additional context validation
        if (!ctx.save || typeof ctx.save !== 'function') {
            console.error('Canvas context is invalid or corrupted')
            return
        }
        
        const data = props.chartData.data

    // Prepare chart data
    const labels = data.map(item => {
        const date = new Date(item.timestamp)
        if (props.timeRange === '24h') {
            return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
        } else {
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
        }
    })

    const eventCounts = data.map(item => item.count)
    const sizeCounts = data.map(item => item.sizeBytes / 1024 / 1024) // Convert to MB

    chartInstance.value = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Events Count',
                    data: eventCounts,
                    borderColor: 'rgb(249, 115, 22)', // Orange
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    yAxisID: 'y',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Data Size (MB)',
                    data: sizeCounts,
                    borderColor: 'rgb(59, 130, 246)', // Blue
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    yAxisID: 'y1',
                    tension: 0.4,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 0 // Disable animations to prevent DOM conflicts
            },
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            if (context.datasetIndex === 1) {
                                return `${formatBytes(context.raw * 1024 * 1024)}`
                            }
                            return ''
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: props.timeRange === '24h' ? 'Time' : 'Date'
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Events Count'
                    },
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Data Size (MB)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                },
            }
        }
    })
} catch (error) {
        console.error('Error creating chart:', error)
        // Clean up on error
        if (chartInstance.value) {
            chartInstance.value.destroy()
            chartInstance.value = null
        }
    }
}

// Simplified watchers to avoid lifecycle issues
const waitForCanvasAndCreateChart = async (retryCount = 0) => {
    // Prevent infinite retries
    if (retryCount > 10) {
        console.warn('Max retries reached, giving up on chart creation')
        return
    }
    
    // Wait for multiple tick cycles to ensure DOM is stable
    await nextTick()
    await nextTick()
    
    // Verify canvas exists and is properly attached
    if (!chartCanvas.value || 
        !document.contains(chartCanvas.value) || 
        !chartCanvas.value.ownerDocument ||
        !chartCanvas.value.clientWidth) {
        // Retry after a short delay
        setTimeout(() => waitForCanvasAndCreateChart(retryCount + 1), 100)
        return
    }
    
    createChart()
}

watch(() => props.chartData, async () => {
    if (props.chartData?.data?.length) {
        setTimeout(waitForCanvasAndCreateChart, 100)
    }
}, { deep: true })

watch(() => props.timeRange, async () => {
    destroyChart()
    if (props.chartData?.data?.length) {
        setTimeout(waitForCanvasAndCreateChart, 150)
    }
})

watch(() => props.selectedDatasource, async () => {
    destroyChart()
    if (props.chartData?.data?.length) {
        setTimeout(waitForCanvasAndCreateChart, 150)
    }
})

// Simplified lifecycle
onMounted(() => {
    if (props.chartData?.data?.length) {
        // Longer delay to ensure DOM is fully rendered and canvas is ready
        setTimeout(waitForCanvasAndCreateChart, 200)
    }
})

// Cleanup
onBeforeUnmount(() => {
    destroyChart()
})
</script>

<style scoped>
.ingestion-chart {
    @apply w-full;
}
</style>