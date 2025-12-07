<template>
    <div class="bg-transparent p-0">
        <!-- Date pagination / toggle -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">From:</label>
                <input 
                    type="datetime-local" 
                    v-model="startDate"
                    @change="$emit('refresh')"
                    class="p-2 border border-gray-300 rounded-lg shadow-sm text-sm"
                />
                <label class="text-sm font-medium text-gray-700">To:</label>
                <input 
                    type="datetime-local" 
                    v-model="endDate"
                    @change="$emit('refresh')"
                    class="p-2 border border-gray-300 rounded-lg shadow-sm text-sm"
                />
            </div>

            <div v-if="!hidePagination && hasMorePages" class="flex items-center gap-2">
                <div class="text-sm text-gray-700 mr-4">
                    <span v-if="totalPages > 1">{{ ((currentPage - 1) * pageSize) + 1 }}-{{ Math.min(currentPage * pageSize, totalItems) }} of {{ totalItems }}</span>
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
                            <span v-else class="text-gray-500 px-1">{{ page }}</span>
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

            <!-- View Toggle Slider -->
            <div v-if="!hideToggle && (hidePagination || !hasMorePages)" class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-700">Events</span>
                <label class="inline-flex items-center cursor-pointer">
                    <input 
                        type="checkbox" 
                        v-model="showAlertsOnly" 
                        @change="$emit('refresh')"
                        class="sr-only peer"
                    />
                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                </label>
                <span class="text-sm font-medium text-gray-700">Alerts Only</span>
            </div>
        </div>

        <!-- Searchbox -->
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-64">
                <input 
                    type="text" 
                    v-model="alertFilter"
                    @input="$emit('filter-change')"
                    :placeholder="searchPlaceholder"
                    class="w-full p-2 border border-gray-300 rounded-lg shadow-sm text-sm"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    modelStartDate: String,
    modelEndDate: String,
    modelShowAlertsOnly: Boolean,
    modelAlertFilter: String,
    searchPlaceholder: {
        type: String,
        default: 'Search...'
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
    pageSize: {
        type: Number,
        default: 25
    },
    hasMorePages: {
        type: Boolean,
        default: false
    },
    hidePagination: {
        type: Boolean,
        default: false
    },
    hideToggle: {
        type: Boolean,
        default: false
    },
    loading: Boolean
})

const emit = defineEmits(['update:modelStartDate', 'update:modelEndDate', 'update:modelShowAlertsOnly', 'update:modelAlertFilter', 'refresh', 'filter-change', 'page-change'])

const startDate = computed({
    get: () => props.modelStartDate,
    set: (value) => emit('update:modelStartDate', value)
})

const endDate = computed({
    get: () => props.modelEndDate,
    set: (value) => emit('update:modelEndDate', value)
})

const showAlertsOnly = computed({
    get: () => props.modelShowAlertsOnly,
    set: (value) => emit('update:modelShowAlertsOnly', value)
})

const alertFilter = computed({
    get: () => props.modelAlertFilter,
    set: (value) => emit('update:modelAlertFilter', value)
})

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
</script>