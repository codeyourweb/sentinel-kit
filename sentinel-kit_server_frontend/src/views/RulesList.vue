<template>
    <h1 class="text-left mb-4">Detection rules list</h1>
    <div v-if="rules.length > 0" class="space-y-4">
        <div class="flex flex-wrap items-center gap-4 p-4 border rounded-lg bg-gray-50 shadow-sm">
            
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
            </select>
            
        </div>
    </div>

    <ul class="mt-6 space-y-3">
        <li v-for="rule in paginatedRules" :key="rule.id">
            <RuleSummary :rule="rule" @update:ruleStatus="handleStatusUpdate" />
        </li>
    </ul>

    <div v-if="totalPages > 1" class="pagination-controls flex justify-center items-center gap-2 mt-6">
        <button 
            @click="goToPage(currentPage - 1)" 
            :disabled="currentPage === 1"
            class="px-3 py-1 border rounded-lg text-gray-700 bg-white hover:bg-gray-100 disabled:opacity-50"
        >
            &laquo; Previous
        </button>

        <template v-for="(page, index) in visiblePages" :key="index">
            <span v-if="page === '...'" class="px-2 text-gray-500">...</span>
            <button 
                v-else
                @click="goToPage(page)" 
                :class="{ 
                    'bg-blue-600 text-white border-blue-600': page === currentPage, 
                    'bg-white text-gray-700 border-gray-300 hover:bg-gray-100': page !== currentPage 
                }"
                class="px-3 py-1 border rounded-lg transition-colors duration-150"
            >
                {{ page }}
            </button>
        </template>
        
        <button 
            @click="goToPage(currentPage + 1)" 
            :disabled="currentPage === totalPages"
            class="px-3 py-1 border rounded-lg text-gray-700 bg-white hover:bg-gray-100 disabled:opacity-50"
        >
            Next &raquo;
        </button>
    </div>

    <p v-if="rules.length === 0" class="mt-6 text-center text-gray-500">Loading rules...</p>
</template>

<script setup>
import RuleSummary from '../components/RuleSummary.vue';
import { ref, computed } from 'vue';
const rules = ref([]);
const BASE_URL = import.meta.env.VITE_API_BASE_URL;

const pageSize = ref(20);
const currentPage = ref(1);
const searchQuery = ref('');
const sortKey = ref('title');

const handleStatusUpdate = ({ ruleId, newStatus }) => {
    if (sortKey.value === 'active') {
        const currentSortKey = sortKey.value;
        sortKey.value = '';
        sortKey.value = currentSortKey;
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
.then(response => response.json())
.then(data => {
    rules.value = data;
})
.catch(error => {
    console.error('Error fetching rules:', error);
});
</script>

<style scoped>
.pagination-controls {
    padding: 20px 0;
}
</style>