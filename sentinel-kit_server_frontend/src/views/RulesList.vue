<!--
/**
 * Rules List View - Detection Rules Management Interface
 * 
 * This view provides a comprehensive interface for managing Sigma detection rules
 * in the Sentinel-Kit platform. It offers advanced filtering, sorting, and bulk
 * operations for efficient rule management.
 * 
 * Features:
 * - Advanced search with title and description filtering
 * - Multiple sorting options (title, date, status, severity, detection count)
 * - Bulk operations for enabling/disabling multiple rules
 * - Rule status indicators with active/inactive states
 * - Detection statistics with 24h, 7d, and 30d metrics
 * - Pagination with configurable page sizes
 * - Real-time rule status updates
 * - Quick access to rule editing and creation
 * 
 * Rule Management:
 * - Create new Sigma rules with guided wizard
 * - Edit existing rules with version history
 * - Enable/disable rules individually or in bulk
 * - View detection statistics and performance metrics
 * - Filter by active status and severity levels
 * 
 * Data Display:
 * - Rule title, description, and metadata
 * - Active/inactive status with toggle controls
 * - Severity levels with color-coded indicators
 * - Detection count statistics for performance analysis
 * - Creation dates and last modification times
 */
-->

<template>
    <!-- Rules Management Container -->
    <div class="container mx-auto px-6">
        <a class="btn btn-primary float-right mb-4 mt-4 rounded" @click="$router.push({ name: 'RuleCreate' })">
            <span class="icon-[material-symbols--save-rounded] bg-white text-white"></span>
            Create new rule
        </a>
        <h1 class="text-4xl font-extrabold text-gray-900 text-left m-4">Detection rules list</h1>
        <br class="clear-both" />
        <div v-if="rules.length > 0" class="space-y-4">
            <div class="flex flex-col gap-4 p-4 border border-orange-400 bg-white shadow-lg">
                <div class="flex flex-wrap items-center gap-4">
                    <p class="text-gray-600 flex-shrink-0 text-sm">
                        Showing {{ paginatedRules.length }} of {{ sortedAndFilteredRules.length }} rules.
                    </p>

                    <input 
                        type="text" 
                        placeholder="Search title or description..." 
                        v-model="searchQuery" 
                        class="p-2 border border-gray-300 rounded-lg shadow-sm flex-grow min-w-[200px]" 
                    />
                    
                    <select v-model="pageSize" @change="updatePageSize($event.target.value)" class="p-2 border border-gray-300 rounded-lg shadow-sm">
                        <option :value="20">20 per page</option>
                        <option :value="100">100 per page</option>
                        <option :value="1000">1000 per page</option>
                    </select>

                    <select v-model="sortKey" @change="updateSort" class="p-2 border border-gray-300 rounded-lg shadow-sm">
                        <option value="title">Sort by Title (A-Z)</option>
                        <option value="createdOn">Creation Date (Newest First)</option>
                        <option value="active">Status (Active First)</option>
                        <option value="level">Severity Level (Critical to info.)</option>
                        <option value="alerts_24h">Detections (1d - Most Active)</option>
                        <option value="alerts_7d">Detections (7d - Most Active)</option>
                        <option value="alerts_30d">Detections (30d - Most Active)</option>
                    </select>
                </div>
                
                <div v-if="searchQuery.trim().length > 0 && sortedAndFilteredRules.length > 0" class="mt-4">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center space-x-2">
                            <span class="icon-[material-symbols--group-work] w-5 h-5 text-orange-400 bg-orange-400"></span>
                            <span class="text-orange-800 font-medium text-sm">
                                Bulk actions on {{ sortedAndFilteredRules.length }} filtered rule{{ sortedAndFilteredRules.length > 1 ? 's' : '' }}:
                            </span>
                        </div>
                        
                        <div class="flex gap-2">
                            <a 
                                @click="handleBulkActionClick('activate', $event)"
                                class="btn btn-success btn-sm px-3 py-1 text-xs font-medium rounded transition duration-150"
                                :class="{
                                    'text-green-800 bg-green-100 border border-green-300 hover:bg-green-200 cursor-pointer': !isBulkActionRunning,
                                    'text-gray-500 bg-gray-100 border border-gray-300 cursor-not-allowed pointer-events-none': isBulkActionRunning
                                }"
                            >
                                <span class="icon-[material-symbols--toggle-on] w-4 h-4 mr-1"></span>
                                Activate All
                            </a>
                            
                            <a 
                                @click="handleBulkActionClick('deactivate', $event)"
                                class="btn btn-warning btn-sm px-3 py-1 text-xs font-medium rounded transition duration-150"
                                :class="{
                                    'text-yellow-800 bg-yellow-100 border border-yellow-300 hover:bg-yellow-200 cursor-pointer': !isBulkActionRunning,
                                    'text-gray-500 bg-gray-100 border border-gray-300 cursor-not-allowed pointer-events-none': isBulkActionRunning
                                }"
                            >
                                <span class="icon-[material-symbols--toggle-off] w-4 h-4 mr-1"></span>
                                Deactivate All
                            </a>
                            
                            <a 
                                @click="handleBulkActionClick('delete', $event)"
                                class="btn btn-error btn-sm px-3 py-1 text-xs font-medium rounded transition duration-150"
                                :class="{
                                    'text-red-800 bg-red-100 border border-red-300 hover:bg-red-200 cursor-pointer': !isBulkActionRunning,
                                    'text-gray-500 bg-gray-100 border border-gray-300 cursor-not-allowed pointer-events-none': isBulkActionRunning
                                }"
                            >
                                <span class="icon-[material-symbols--delete-sweep] w-4 h-4 mr-1"></span>
                                Delete All
                            </a>
                        </div>
                        
                        <div v-if="isBulkActionRunning" class="flex items-center space-x-2 text-orange-600">
                            <span class="icon-[svg-spinners--ring-resize] w-4 h-4 animate-spin"></span>
                            <span class="text-sm">Processing...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rules List or Loading Skeleton -->
        <div v-if="isLoading" class="mt-6 space-y-3">
            <!-- Skeleton for RuleSummary components -->
            <div v-for="i in 5" :key="i" class="animate-pulse border-l-4 border-gray-300 p-4 mb-4 rounded-lg shadow-sm bg-gray-100">
                <div class="flex justify-between items-center mb-3">
                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                        <!-- Edit button skeleton -->
                        <div class="w-9 h-9 bg-gray-300 rounded-full"></div>
                        <!-- Title skeleton -->
                        <div class="h-6 bg-gray-300 rounded w-1/3"></div>
                    </div>
                    <!-- Toggle switch skeleton -->
                    <div class="w-11 h-6 bg-gray-300 rounded-full"></div>
                </div>
                <!-- Description skeleton -->
                <div class="h-4 bg-gray-300 rounded w-3/4 mb-4"></div>
                <!-- Footer skeleton -->
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <!-- Severity badge skeleton -->
                        <div class="h-5 bg-gray-300 rounded-full w-16"></div>
                        <!-- Detection stats skeleton -->
                        <div class="h-4 bg-gray-300 rounded w-20"></div>
                        <div class="h-4 bg-gray-300 rounded w-20"></div>
                        <div class="h-4 bg-gray-300 rounded w-20"></div>
                        <div class="h-4 bg-gray-300 rounded w-20"></div>
                    </div>
                    <!-- Delete button skeleton -->
                    <div class="w-8 h-8 bg-gray-300 rounded-lg"></div>
                </div>
            </div>
        </div>

        <ul v-else class="mt-6 space-y-3">
            <li v-for="rule in paginatedRules" :key="rule.id">
                <RuleSummary 
                    :rule="rule" 
                    :isDeleting="deletingRules.has(rule.id)"
                    @update:ruleStatus="handleStatusUpdate" 
                    @deleteRule="handleDeleteRule"
                    @show-notification="(notification) => emit('show-notification', notification)"
                />
            </li>
        </ul>

        <div v-if="totalPages > 1" class="pagination-controls flex justify-center items-center gap-2 mt-6">
            <a 
                @click="goToPage(currentPage - 1)" 
                :disabled="currentPage === 1"
                class="px-3 py-1 border rounded-lg text-gray-700 bg-white hover:bg-gray-100 disabled:opacity-50"
            >
                &laquo; Previous
            </a>

            <template v-for="(page, index) in visiblePages" :key="index">
                <span v-if="page === '...'" class="px-2 text-gray-500">...</span>
                <a 
                    v-else
                    @click="goToPage(page)" 
                    :class="{ 
                        'bg-orange-400 text-white border-orange-400': page === currentPage, 
                        'bg-white text-gray-700 border-gray-300 hover:bg-gray-100': page !== currentPage 
                    }"
                    class="px-3 py-1 border rounded-lg transition-colors duration-150"
                >
                    {{ page }}
                </a>
            </template>
            
            <a 
                @click="goToPage(currentPage + 1)" 
                :disabled="currentPage === totalPages"
                class="px-3 py-1 border rounded-lg text-gray-700 bg-white hover:bg-gray-100 disabled:opacity-50"
            >
                Next &raquo;
            </a>
        </div>

        <p v-if="!isLoading && rules.length === 0" class="mt-6 text-center text-gray-500">No detection rule stored yet</p>

        <div 
            v-if="bulkActionModal.visible" 
            class="flex fixed inset-0 bg-gray-200 bg-opacity-80 backdrop-opacity-80 items-center justify-center z-50"
            @click="closeBulkActionModal"
        >
            <div 
                class="bg-white rounded-lg p-6 max-w-md mx-4 shadow-xl"
                @click.stop
            >
                <div class="flex items-center mb-4">
                    <span 
                        class="w-6 h-6 mr-3"
                        :class="{
                            'icon-[material-symbols--warning] text-red-500': bulkActionModal.action === 'delete',
                            'icon-[material-symbols--info] text-orange-400': bulkActionModal.action !== 'delete'
                        }"
                    ></span>
                    <h3 class="text-lg font-semibold text-gray-900">
                        Confirm bulk action
                    </h3>
                </div>
                
                <p class="text-gray-600 mb-6">
                    <span v-if="bulkActionModal.action === 'activate'">
                        <template v-if="getRulesCountForAction('activate') > 0">
                            Are you sure you want to <strong>activate</strong> {{ getRulesCountForAction('activate') }} rule{{ getRulesCountForAction('activate') > 1 ? 's' : '' }}?
                            <span v-if="getRulesCountForAction('activate') < sortedAndFilteredRules.length" class="text-sm text-gray-500">
                                <br>({{ sortedAndFilteredRules.length - getRulesCountForAction('activate') }} rule{{ sortedAndFilteredRules.length - getRulesCountForAction('activate') > 1 ? 's are' : ' is' }} already active)
                            </span>
                        </template>
                        <template v-else>
                            All {{ sortedAndFilteredRules.length }} filtered rule{{ sortedAndFilteredRules.length > 1 ? 's are' : ' is' }} already <strong>active</strong>.
                        </template>
                    </span>
                    <span v-else-if="bulkActionModal.action === 'deactivate'">
                        <template v-if="getRulesCountForAction('deactivate') > 0">
                            Are you sure you want to <strong>deactivate</strong> {{ getRulesCountForAction('deactivate') }} rule{{ getRulesCountForAction('deactivate') > 1 ? 's' : '' }}?
                            <span v-if="getRulesCountForAction('deactivate') < sortedAndFilteredRules.length" class="text-sm text-gray-500">
                                <br>({{ sortedAndFilteredRules.length - getRulesCountForAction('deactivate') }} rule{{ sortedAndFilteredRules.length - getRulesCountForAction('deactivate') > 1 ? 's are' : ' is' }} already inactive)
                            </span>
                        </template>
                        <template v-else>
                            All {{ sortedAndFilteredRules.length }} filtered rule{{ sortedAndFilteredRules.length > 1 ? 's are' : ' is' }} already <strong>inactive</strong>.
                        </template>
                    </span>
                    <span v-else-if="bulkActionModal.action === 'delete'">
                        Are you sure you want to <strong>delete</strong> all {{ sortedAndFilteredRules.length }} filtered rule{{ sortedAndFilteredRules.length > 1 ? 's' : '' }}? 
                        <br><strong class="text-red-600">This action is irreversible.</strong>
                    </span>
                </p>
                
                <div class="flex justify-end space-x-3">
                    <a
                        @click="closeBulkActionModal"
                        class="px-4 py-2 text-gray-600 bg-gray-200 hover:bg-gray-300 rounded-lg transition duration-150 cursor-pointer"
                    >
                        Cancel
                    </a>
                    <a
                        @click="(bulkActionModal.action === 'activate' && getRulesCountForAction('activate') === 0) || (bulkActionModal.action === 'deactivate' && getRulesCountForAction('deactivate') === 0) ? null : executeBulkAction"
                        class="px-4 py-2 text-white rounded-lg transition duration-150"
                        :class="{
                            'bg-green-600 hover:bg-green-700 cursor-pointer': bulkActionModal.action === 'activate' && getRulesCountForAction('activate') > 0,
                            'bg-yellow-600 hover:bg-yellow-700 cursor-pointer': bulkActionModal.action === 'deactivate' && getRulesCountForAction('deactivate') > 0,
                            'bg-red-600 hover:bg-red-700 cursor-pointer': bulkActionModal.action === 'delete',
                            'bg-gray-400 cursor-not-allowed': (bulkActionModal.action === 'activate' && getRulesCountForAction('activate') === 0) || (bulkActionModal.action === 'deactivate' && getRulesCountForAction('deactivate') === 0)
                        }"
                    >
                        <span v-if="bulkActionModal.action === 'activate'">
                            <template v-if="getRulesCountForAction('activate') > 0">Activate {{ getRulesCountForAction('activate') }} rule{{ getRulesCountForAction('activate') > 1 ? 's' : '' }}</template>
                            <template v-else>All Already Active</template>
                        </span>
                        <span v-else-if="bulkActionModal.action === 'deactivate'">
                            <template v-if="getRulesCountForAction('deactivate') > 0">Deactivate {{ getRulesCountForAction('deactivate') }} rule{{ getRulesCountForAction('deactivate') > 1 ? 's' : '' }}</template>
                            <template v-else>All Already Inactive</template>
                        </span>
                        <span v-else-if="bulkActionModal.action === 'delete'">Delete All</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import RuleSummary from '../components/RuleSummary.vue';
import { ref, computed, defineEmits } from 'vue';

const emit = defineEmits(['show-notification']);

const rules = ref([]);
const isLoading = ref(true);
const BASE_URL = import.meta.env.VITE_API_BASE_URL;

const pageSize = ref(20);
const currentPage = ref(1);
const searchQuery = ref('');
const sortKey = ref('title');
const deletingRules = ref(new Set());

const isBulkActionRunning = ref(false);
const bulkActionModal = ref({
    visible: false,
    action: null // 'activate', 'deactivate', 'delete'
});

const rulesToActivate = computed(() => {
    return sortedAndFilteredRules.value.filter(rule => !rule.active);
});

const rulesToDeactivate = computed(() => {
    return sortedAndFilteredRules.value.filter(rule => rule.active);
});

const getRulesCountForAction = computed(() => {
    return (action) => {
        switch (action) {
            case 'activate':
                return rulesToActivate.value.length;
            case 'deactivate':
                return rulesToDeactivate.value.length;
            case 'delete':
                return sortedAndFilteredRules.value.length;
            default:
                return 0;
        }
    };
});

const handleStatusUpdate = ({ ruleId, newStatus }) => {
    if (sortKey.value === 'active') {
        const currentSortKey = sortKey.value;
        sortKey.value = '';
        sortKey.value = currentSortKey;
    }
};

const handleDeleteRule = async (ruleId) => {
    deletingRules.value.add(ruleId);
    
    try {
        const response = await fetch(`${BASE_URL}/rules/sigma/${ruleId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                'Content-Type': 'application/json'
            }
        });

        if (response.ok) {
            const ruleIndex = rules.value.findIndex(rule => rule.id === ruleId);
            if (ruleIndex !== -1) {
                const deletedRule = rules.value[ruleIndex];
                rules.value.splice(ruleIndex, 1);
                
                emit('show-notification', {
                    type: 'info',
                    message: `Rule "${deletedRule.title}" deleted successfully`
                });
            }
        } else {
            let errorMessage = 'Unknown error';
            try {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const errorData = await response.json();
                    errorMessage = errorData.message || errorData.error || 'Unknown error';
                } else {
                    errorMessage = `HTTP ${response.status}: ${response.statusText}`;
                }
            } catch (parseError) {
                errorMessage = `HTTP ${response.status}: ${response.statusText}`;
            }
            
            emit('show-notification', {
                type: 'error',
                message: `Error deleting rule: ${errorMessage}`
            });
        }
    } catch (error) {
        console.error('Error deleting rule:', error);
        emit('show-notification', {
            type: 'error',
            message: 'Network error while deleting the rule'
        });
    } finally {
        deletingRules.value.delete(ruleId);
    }
};

const updateSort = (event) => {
    sortKey.value = event.target.value;
    currentPage.value = 1; 
};

const filteredRules = computed(() => {
    if (!searchQuery.value) {
        return rules.value;
    }
    const query = searchQuery.value.toLowerCase();
    
    return rules.value.filter(rule =>
        rule.title.toLowerCase().includes(query) ||
        (rule.description && rule.description.toLowerCase().includes(query))
    );
});

const sortedAndFilteredRules = computed(() => {
    const list = [...filteredRules.value]; 
    list.sort((a, b) => {
        const key = sortKey.value;

        if (key === 'createdOn') {
            const dateA = new Date(a[key]);
            const dateB = new Date(b[key]);
            return dateB - dateA; 
        }

        if (key === 'active') {
            if (a[key] === b[key]) return 0;
            return a[key] ? -1 : 1;
        }

        if (key === 'level') {
            const severityOrder = {
                'critical': 0,
                'high': 1,
                'medium': 2,
                'low': 3,
                'informational': 4
            };
            
            const levelA = a[key] || 'informational';
            const levelB = b[key] || 'informational';
            
            const orderA = severityOrder[levelA] !== undefined ? severityOrder[levelA] : 4;
            const orderB = severityOrder[levelB] !== undefined ? severityOrder[levelB] : 4;
            
            return orderA - orderB;
        }


        if (key === 'alerts_24h' || key === 'alerts_7d' || key === 'alerts_30d') {
            const countA = a[key] || 0;
            const countB = b[key] || 0;
            return countB - countA;
        }

        if (typeof a[key] === 'string' && typeof b[key] === 'string') {
            return a[key].localeCompare(b[key]);
        }
        
        if (a[key] < b[key]) return -1;
        if (a[key] > b[key]) return 1;
        return 0;
    });

    return list;
});

const updatePageSize = (newSize) => {
    pageSize.value = Number(newSize);
    currentPage.value = 1; 
};

const paginatedRules = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value;
    const end = start + pageSize.value;
    return sortedAndFilteredRules.value.slice(start, end); 
});

const totalPages = computed(() => {
    return Math.ceil(sortedAndFilteredRules.value.length / pageSize.value);
});

const visiblePages = computed(() => {
    const pages = [];
    const maxVisible = 5;
    const startPage = Math.max(1, currentPage.value - Math.floor(maxVisible / 2));
    const endPage = Math.min(totalPages.value, startPage + maxVisible - 1);

    if (endPage - startPage + 1 < maxVisible) {
        const newStart = Math.max(1, endPage - maxVisible + 1);
        for (let i = newStart; i <= endPage; i++) {
            pages.push(i);
        }
    } else {
        for (let i = startPage; i <= endPage; i++) {
            pages.push(i);
        }
    }
    
    if (!pages.includes(1)) {
        pages.unshift(1);
        if (pages[1] > 2) {
            pages.splice(1, 0, '...');
        }
    }
    if (!pages.includes(totalPages.value)) {
        if (pages[pages.length - 1] < totalPages.value - 1) {
            pages.push('...');
        }
        pages.push(totalPages.value);
    }
    
    return pages.filter((page, index) => {
        return page !== '...' || (index > 0 && pages[index - 1] !== '...');
    });
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
};

fetch(`${BASE_URL}/rules/sigma/list`, {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json',
        'authorization': `Bearer ${localStorage.getItem('auth_token')}`,
    },
})
.then(response => {    
    if (!response.ok) {
        return response.text().then(text => {
            console.error(`HTTP ${response.status} error response:`, text.substring(0, 500));
            throw new Error(`HTTP ${response.status}: ${response.statusText}. Response: ${text.substring(0, 200)}`);
        });
    }
    
    const contentType = response.headers.get('content-type');
    
    if (!contentType || !contentType.includes('application/json')) {
        return response.text().then(text => {
            console.error('Expected JSON but received:', text.substring(0, 200) + (text.length > 200 ? '...' : ''));
            throw new Error(`Expected JSON response but got: ${contentType}. Response: ${text.substring(0, 100)}`);
        });
    }
    
    return response.json();
})
.then(data => {
    rules.value = data;
    isLoading.value = false;
})
.catch(error => {
    console.error('Error fetching rules:', error);
    
    emit('show-notification', { 
        message: `Failed to load rules: ${error.message}`, 
        type: 'error' 
    });
    
    isLoading.value = false;
});

const handleBulkActionClick = (action, event) => {
    if (isBulkActionRunning.value) {
        event.preventDefault();
        return;
    }
    showBulkActionModal(action);
};

const showBulkActionModal = (action) => {
    bulkActionModal.value = {
        visible: true,
        action: action
    };
};

const closeBulkActionModal = () => {
    bulkActionModal.value = {
        visible: false,
        action: null
    };
};

const executeBulkAction = async () => {
    const action = bulkActionModal.value.action;
    let rulesToProcess = sortedAndFilteredRules.value.slice();
    
    if (action === 'activate') {
        rulesToProcess = rulesToProcess.filter(rule => !rule.active);
    } else if (action === 'deactivate') {
        rulesToProcess = rulesToProcess.filter(rule => rule.active);
    }
    
    closeBulkActionModal();
    
    if (rulesToProcess.length === 0) {
        let message = '';
        if (action === 'activate') {
            message = 'All filtered rules are already active';
        } else if (action === 'deactivate') {
            message = 'All filtered rules are already inactive';
        }
        
        if (message) {
            emit('show-notification', {
                type: 'info',
                message: message
            });
        }
        return;
    }
    
    isBulkActionRunning.value = true;
    
    let successCount = 0;
    let errorCount = 0;
    let skippedCount = sortedAndFilteredRules.value.length - rulesToProcess.length;
    
    try {
        for (const rule of rulesToProcess) {
            try {
                if (action === 'delete') {
                    const response = await fetch(`${BASE_URL}/rules/sigma/${rule.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    if (response.ok) {
                        const ruleIndex = rules.value.findIndex(r => r.id === rule.id);
                        if (ruleIndex !== -1) {
                            rules.value.splice(ruleIndex, 1);
                        }
                        successCount++;
                    } else {
                        errorCount++;
                    }
                } else {
                    const newStatus = action === 'activate';
                    const response = await fetch(`${BASE_URL}/rules/sigma/${rule.id}/status`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                        },
                        body: JSON.stringify({ active: newStatus })
                    });
                    
                    if (response.ok) {
                        const ruleIndex = rules.value.findIndex(r => r.id === rule.id);
                        if (ruleIndex !== -1) {
                            rules.value[ruleIndex].active = newStatus;
                        }
                        successCount++;
                    } else {
                        errorCount++;
                    }
                }
            } catch (error) {
                console.error(`Error processing rule ${rule.id}:`, error);
                errorCount++;
            }
        }
        
        if (successCount > 0) {
            let message = '';
            if (action === 'delete') {
                message = `${successCount} rule${successCount > 1 ? 's' : ''} deleted successfully`;
            } else if (action === 'activate') {
                message = `${successCount} rule${successCount > 1 ? 's' : ''} activated successfully`;
                if (skippedCount > 0) {
                    message += ` (${skippedCount} already active)`;
                }
            } else if (action === 'deactivate') {
                message = `${successCount} rule${successCount > 1 ? 's' : ''} deactivated successfully`;
                if (skippedCount > 0) {
                    message += ` (${skippedCount} already inactive)`;
                }
            }
            
            emit('show-notification', {
                type: 'success',
                message: message
            });
        }
        
        if (errorCount > 0) {
            emit('show-notification', {
                type: 'error',
                message: `${errorCount} rule${errorCount > 1 ? 's' : ''} failed to process`
            });
        }
        
    } catch (error) {
        console.error('Bulk action error:', error);
        emit('show-notification', {
            type: 'error',
            message: 'An error occurred during bulk action execution'
        });
    } finally {
        isBulkActionRunning.value = false;
    }
};
</script>

<style scoped>
.pagination-controls {
    padding: 20px 0;
}
</style>