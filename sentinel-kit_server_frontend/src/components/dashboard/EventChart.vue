<template>
    <div class="bg-white">
        <div v-if="!hideTitle" class="flex justify-between items-center p-6 border-b border-gray-200">
            <div class="flex items-center gap-4">
                <h2 class="text-2xl font-bold text-gray-800">
                    {{ showAlertsOnly ? 'Alerts' : 'Events' }} Volume Over Time
                </h2>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600">
                        Total: {{ totalCount.toLocaleString() }} {{ showAlertsOnly ? 'alerts' : 'events' }}
                    </span>
                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                        Interval: {{ currentInterval }}
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <!-- Pagination -->
                <div v-if="showPagination" class="flex items-center space-x-2">
                    <div class="text-sm text-gray-700 mr-4">
                        <span v-if="totalPages > 1">{{ ((currentPage - 1) * 100) + 1 }}-{{ Math.min(currentPage * 100, totalItems) }} of {{ totalItems }}</span>
                        <span v-else>{{ totalItems }} item{{ totalItems > 1 ? 's' : '' }}</span>
                    </div>
                    <template v-if="totalPages > 1">
                        <a
                            @click="$emit('page-change', Math.max(1, currentPage - 1))"
                            :class="['btn btn-info text-xs px-2 py-1', { 'opacity-50 cursor-not-allowed': currentPage === 1 }]"
                            href="#"
                        >
                            ‹
                        </a>
                        
                        <!-- Page numbers -->
                        <div class="flex items-center space-x-1">
                            <template v-for="page in getVisiblePages()" :key="page">
                                <a
                                    v-if="page !== '...'"
                                    @click="$emit('page-change', page)"
                                    :class="['btn btn-info text-xs px-2 py-1', { 'opacity-75': page === currentPage }]"
                                    href="#"
                                >
                                    {{ page }}
                                </a>
                                <span v-else class="px-1 py-1 text-xs text-gray-500">...</span>
                            </template>
                        </div>
                        
                        <a
                            @click="$emit('page-change', Math.min(totalPages, currentPage + 1))"
                            :class="['btn btn-info text-xs px-2 py-1', { 'opacity-50 cursor-not-allowed': currentPage === totalPages }]"
                            href="#"
                        >
                            ›
                        </a>
                    </template>
                </div>

                <!-- Interval Selector -->
                <div class="flex items-center gap-2">
                    <label for="interval-select" class="text-sm text-gray-600 font-medium">Interval:</label>
                    <select 
                        id="interval-select"
                        v-model="selectedInterval" 
                        @change="onIntervalChange"
                        class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors duration-200 hover:border-gray-400"
                    >
                        <option value="">Auto</option>
                        <option 
                            v-for="interval in availableIntervals" 
                            :key="interval.value"
                            :value="interval.value"
                        >
                            {{ interval.label }}
                        </option>
                    </select>
                </div>
                <a  
                    @click="$emit('update:collapsed', !collapsed)"
                    class="btn btn-primary flex items-center gap-2 px-3 py-2 text-sm rounded-lg"
                >
                    <span class="icon-[material-symbols--expand-less]" 
                          :class="{ 'rotate-180': collapsed }"></span>
                    {{ collapsed ? 'Show' : 'Hide' }} Chart
                </a>
            </div>
        </div>
        
        <div v-else class="flex justify-between items-center p-4 pb-2">
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-600">
                    Total: {{ totalCount.toLocaleString() }} {{ showAlertsOnly ? 'alerts' : 'events' }}
                </span>
                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                    Interval: {{ currentInterval }}
                </span>
            </div>
            
            <div class="flex items-center gap-3">
                <!-- Pagination -->
                <div v-if="showPagination" class="flex items-center space-x-2">
                    <div class="text-sm text-gray-700 mr-4">
                        <span v-if="totalPages > 1">{{ ((currentPage - 1) * 100) + 1 }}-{{ Math.min(currentPage * 100, totalItems) }} of {{ totalItems }}</span>
                        <span v-else>{{ totalItems }} item{{ totalItems > 1 ? 's' : '' }}</span>
                    </div>
                    <template v-if="totalPages > 1">
                        <a
                            @click="$emit('page-change', Math.max(1, currentPage - 1))"
                            :class="['btn btn-info text-xs px-2 py-1', { 'opacity-50 cursor-not-allowed': currentPage === 1 }]"
                            href="#"
                        >
                            ‹
                        </a>
                        
                        <!-- Page numbers -->
                        <div class="flex items-center space-x-1">
                            <template v-for="page in getVisiblePages()" :key="page">
                                <a
                                    v-if="page !== '...'"
                                    @click="$emit('page-change', page)"
                                    :class="['btn btn-info text-xs px-2 py-1', { 'opacity-75': page === currentPage }]"
                                    href="#"
                                >
                                    {{ page }}
                                </a>
                                <span v-else class="px-1 py-1 text-xs text-gray-500">...</span>
                            </template>
                        </div>
                        
                        <a
                            @click="$emit('page-change', Math.min(totalPages, currentPage + 1))"
                            :class="['btn btn-info text-xs px-2 py-1', { 'opacity-50 cursor-not-allowed': currentPage === totalPages }]"
                            href="#"
                        >
                            ›
                        </a>
                    </template>
                </div>

                <!-- Interval Selector -->
                <div class="flex items-center gap-2">
                    <label for="interval-select" class="text-sm text-gray-600">Interval:</label>
                    <select 
                        id="interval-select"
                        v-model="selectedInterval" 
                        @change="onIntervalChange"
                        class="text-sm border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    >
                        <option value="">Auto</option>
                        <option 
                            v-for="interval in availableIntervals" 
                            :key="interval.value"
                            :value="interval.value"
                        >
                            {{ interval.label }}
                        </option>
                    </select>
                </div>
                <a  
                    @click="$emit('update:collapsed', !collapsed)"
                    class="btn btn-primary flex items-center gap-2 px-3 py-2 text-sm rounded-lg"
                >
                    <span class="icon-[material-symbols--expand-less]" 
                          :class="{ 'rotate-180': collapsed }"></span>
                    {{ collapsed ? 'Show' : 'Hide' }} Chart
                </a>
            </div>
        </div>
        <div v-if="!collapsed" class="px-4 pb-4">
            <div v-if="loading" class="animate-pulse h-64 bg-gray-100 rounded-lg flex flex-col justify-center items-center space-y-4">
                <!-- Chart skeleton -->
                <div class="w-full h-48 bg-gray-200 rounded flex items-end justify-around px-4">
                    <!-- Bars skeleton -->
                    <div class="w-6 bg-gray-300 rounded-t" style="height: 60%"></div>
                    <div class="w-6 bg-gray-300 rounded-t" style="height: 80%"></div>
                    <div class="w-6 bg-gray-300 rounded-t" style="height: 40%"></div>
                    <div class="w-6 bg-gray-300 rounded-t" style="height: 90%"></div>
                    <div class="w-6 bg-gray-300 rounded-t" style="height: 70%"></div>
                    <div class="w-6 bg-gray-300 rounded-t" style="height: 50%"></div>
                    <div class="w-6 bg-gray-300 rounded-t" style="height: 85%"></div>
                    <div class="w-6 bg-gray-300 rounded-t" style="height: 65%"></div>
                </div>
                <!-- X-axis skeleton -->
                <div class="w-full flex justify-around px-4">
                    <div class="h-3 bg-gray-300 rounded w-12"></div>
                    <div class="h-3 bg-gray-300 rounded w-12"></div>
                    <div class="h-3 bg-gray-300 rounded w-12"></div>
                    <div class="h-3 bg-gray-300 rounded w-12"></div>
                    <div class="h-3 bg-gray-300 rounded w-12"></div>
                </div>
            </div>
            <div v-else class="h-64 relative">
                <!-- Selection Help -->
                <div class="text-xs text-gray-500 italic flex items-center gap-1 mb-2">
                    <span class="icon-[material-symbols--drag-pan] w-3 h-3"></span>
                    Drag on chart to zoom into time period
                </div>
                <Bar 
                    ref="chartRef"
                    :data="chartData" 
                    :options="chartOptions" 
                    :key="chartKey"
                />
                <!-- Selection overlay -->
                <div 
                    v-if="isSelecting" 
                    class="absolute top-0 pointer-events-none"
                    :style="selectionOverlayStyle"
                >
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { Bar } from 'vue-chartjs'
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    TimeScale
} from 'chart.js'
import zoomPlugin from 'chartjs-plugin-zoom'
import 'chartjs-adapter-date-fns'
import { useChartTimeUtils } from '../../composables/useChartTimeUtils'
import { useChartSelection } from '../../composables/useChartSelection'

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    TimeScale,
    zoomPlugin
)

const props = defineProps({
    chartData: Object,
    chartOptions: Object,
    chartKey: Number,
    loading: Boolean,
    collapsed: Boolean,
    showAlertsOnly: Boolean,
    totalCount: Number,
    currentInterval: String,
    startDate: String,
    endDate: String,
    customInterval: String,
    showPagination: {
        type: Boolean,
        default: false
    },
    currentPage: {
        type: Number,
        default: 1
    },
    totalPages: {
        type: Number,
        default: 1
    },
    totalItems: {
        type: Number,
        default: 0
    },
    hideTitle: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:collapsed', 'date-range-selected', 'interval-changed', 'page-change'])

const chartRef = ref(null)
const selectedInterval = ref(props.customInterval || '')

const { getAvailableIntervals } = useChartTimeUtils()

const availableIntervals = computed(() => {
    if (!props.startDate || !props.endDate) return []
    
    const startTime = new Date(props.startDate).toISOString()
    const endTime = new Date(props.endDate).toISOString()
    
    return getAvailableIntervals(startTime, endTime)
})

const onIntervalChange = () => {
    emit('interval-changed', selectedInterval.value)
}

// Watch for changes in custom interval prop
watch(() => props.customInterval, (newInterval) => {
    selectedInterval.value = newInterval || ''
}, { immediate: true })

const getVisiblePages = () => {
    const pages = []
    const total = props.totalPages
    const current = props.currentPage
    
    if (total <= 5) {
        for (let i = 1; i <= total; i++) {
            pages.push(i)
        }
    } else {
        pages.push(1)
        
        if (current > 3) {
            pages.push('...')
        }
        
        const start = Math.max(2, current - 1)
        const end = Math.min(total - 1, current + 1)
        
        for (let i = start; i <= end; i++) {
            if (i !== 1 && i !== total) {
                pages.push(i)
            }
        }
        
        if (current < total - 2) {
            pages.push('...')
        }
        
        if (total > 1) {
            pages.push(total)
        }
    }
    
    return pages
}

const { 
    isSelecting, 
    selectionOverlayStyle, 
    setupChartSelection 
} = useChartSelection(chartRef, emit)

watch(() => props.chartKey, () => {
    nextTick(() => {
        setTimeout(() => {
            setupChartSelection()
        }, 500)
    })
})

onMounted(() => {
    nextTick(() => {
        setupChartSelection()
        
        setTimeout(() => {
            setupChartSelection()
        }, 1000)
    })
})
</script>

<style scoped>
.rotate-180 {
    transform: rotate(180deg);
}

[class*="icon-[material-symbols--expand-less]"] {
    transition: transform 0.3s ease;
}
</style>