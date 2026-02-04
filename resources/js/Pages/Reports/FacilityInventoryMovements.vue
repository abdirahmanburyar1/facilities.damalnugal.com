<template>
    <Head title="Facility Inventory Movements Report" />
    
    <AuthenticatedLayout 
        title="Facility Inventory Movements Report"
        description="Track all inventory movements including transfers, orders, and dispenses"
        img="/assets/images/inventory.png"
    >
        <div class="mb-[100px]">
            <!-- Header & Actions -->
            <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-4">
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Facility Inventory Movements</h1>
                <div class="flex flex-wrap gap-2 md:gap-4 items-center">
                    <button
                        @click="exportData"
                        :disabled="isExporting"
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 border border-transparent rounded-lg font-medium text-sm text-white hover:from-green-600 hover:to-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 shadow-sm disabled:opacity-50"
                    >
                        <svg v-if="!isExporting" class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <svg v-else class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isExporting ? 'Exporting...' : 'Export CSV' }}
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Movements</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ formatNumber(summary.total_movements) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 12l2 2 4-4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Received</p>
                            <p class="text-2xl font-semibold text-green-600">{{ formatNumber(summary.total_received) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Issued</p>
                            <p class="text-2xl font-semibold text-red-600">{{ formatNumber(summary.total_issued) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Net Change</p>
                            <p class="text-2xl font-semibold" :class="netChange >= 0 ? 'text-green-600' : 'text-red-600'">
                                {{ netChange >= 0 ? '+' : '' }}{{ formatNumber(netChange) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="bg-white rounded-xl shadow-md p-4 mb-4 border border-gray-200">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input 
                            v-model="search" 
                            type="text" 
                            class="w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2" 
                            placeholder="Reference, batch, barcode, product..."
                        />
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Movement Type</label>
                        <select 
                            v-model="movement_type"
                            class="w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2"
                        >
                            <option value="">All Types</option>
                            <option v-for="(label, value) in movement_types" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Source Type</label>
                        <select 
                            v-model="source_type"
                            class="w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2"
                        >
                            <option value="">All Sources</option>
                            <option v-for="(label, value) in source_types" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                        <Multiselect
                            v-model="product_id"
                            :options="products"
                            :searchable="true"
                            :close-on-select="true"
                            :show-labels="false"
                            placeholder="Select product"
                            label="name"
                            track-by="id"
                            :allow-empty="true"
                            class="w-full"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input 
                            v-model="date_from" 
                            type="date" 
                            class="w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input 
                            v-model="date_to" 
                            type="date" 
                            class="w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2"
                        />
                    </div>
                </div>
                
                <div class="flex justify-end items-center gap-4 mt-3">
                    <select 
                        v-model="per_page" 
                        class="rounded-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-[150px]"
                        @change="filters.page = 1"
                    >
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                        <option value="200">200 per page</option>
                    </select>
                </div>
            </div>

            <!-- Movements Table -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Movement</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch/Barcode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="movements.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        <p class="text-lg font-medium">No movements found</p>
                                        <p class="text-sm">Try adjusting your filters</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-else v-for="movement in movements.data" :key="movement.id" class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDate(movement.movement_date) }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div>
                                        <div class="font-medium text-gray-900">{{ movement.product?.name }}</div>
                                        <div class="text-gray-500">{{ movement.product?.category?.name }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getMovementTypeClass(movement.movement_type)}`">
                                        {{ formatMovementType(movement.movement_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getSourceTypeClass(movement.source_type)}`">
                                        {{ formatSourceType(movement.source_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ movement.reference_number || '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="space-y-1">
                                        <div v-if="movement.batch_number">Batch: {{ movement.batch_number }}</div>
                                        <div v-if="movement.barcode">Barcode: {{ movement.barcode }}</div>
                                        <div v-if="movement.uom">UoM: {{ movement.uom }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div v-if="movement.facility_received_quantity > 0" class="text-green-600">
                                        +{{ formatNumber(movement.facility_received_quantity) }}
                                    </div>
                                    <div v-if="movement.facility_issued_quantity > 0" class="text-red-600">
                                        -{{ formatNumber(movement.facility_issued_quantity) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDate(movement.expiry_date) || '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="bg-white px-4 py-3 border-t border-gray-200">
                    <TailwindPagination
                        :data="movements"
                        @pagination-change-page="getResults"
                        :limit="3"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, watch } from 'vue';
import { TailwindPagination } from 'laravel-vue-pagination';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import '@/Components/multiselect.css';
import moment from 'moment';

const props = defineProps({
    movements: Object,
    summary: Object,
    products: Array,
    filters: Object,
    movement_types: Object,
    source_types: Object
});

// Reactive state
const isExporting = ref(false);
const search = ref(props.filters.search || '');
const movement_type = ref(props.filters.movement_type || '');
const source_type = ref(props.filters.source_type || '');
const product_id = ref(props.filters.product_id || null);
const date_from = ref(props.filters.date_from || '');
const date_to = ref(props.filters.date_to || '');
const per_page = ref(props.filters.per_page || 25);

// Computed
const netChange = computed(() => {
    return (props.summary.total_received || 0) - (props.summary.total_issued || 0);
});

// Watch for filter changes
watch([
    () => search.value,
    () => movement_type.value,
    () => source_type.value,
    () => product_id.value,
    () => date_from.value,
    () => date_to.value,
    () => per_page.value
], () => {
    applyFilters();
});

// Methods
function applyFilters() {
    const query = {};
    if (search.value) query.search = search.value;
    if (movement_type.value) query.movement_type = movement_type.value;
    if (source_type.value) query.source_type = source_type.value;
    if (product_id.value) query.product_id = product_id.value.id;
    if (date_from.value) query.date_from = date_from.value;
    if (date_to.value) query.date_to = date_to.value;
    if (per_page.value) query.per_page = per_page.value;

    router.get(route('reports.facility-inventory-movements.index'), query, {
        preserveState: true,
        preserveScroll: true,
        only: ['movements', 'summary']
    });
}

function getResults(page = 1) {
    props.filters.page = page;
}

function formatNumber(value) {
    return parseFloat(value || 0).toLocaleString();
}

function formatDate(date) {
    return date ? moment(date).format('DD/MM/YYYY') : '';
}

function formatMovementType(type) {
    return props.movement_types[type] || type;
}

function formatSourceType(type) {
    return props.source_types[type] || type;
}

function getMovementTypeClass(type) {
    const classes = {
        'facility_received': 'bg-green-100 text-green-800',
        'facility_issued': 'bg-red-100 text-red-800'
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
}

function getSourceTypeClass(type) {
    const classes = {
        'transfer': 'bg-blue-100 text-blue-800',
        'order': 'bg-purple-100 text-purple-800',
        'dispense': 'bg-orange-100 text-orange-800',
        'moh_dispense': 'bg-emerald-100 text-emerald-800'
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
}

async function exportData() {
    isExporting.value = true;
    try {
        const query = new URLSearchParams();
        if (search.value) query.append('search', search.value);
        if (movement_type.value) query.append('movement_type', movement_type.value);
        if (source_type.value) query.append('source_type', source_type.value);
        if (product_id.value) query.append('product_id', product_id.value.id);
        if (date_from.value) query.append('date_from', date_from.value);
        if (date_to.value) query.append('date_to', date_to.value);

        window.location.href = route('reports.facility-inventory-movements.export') + '?' + query.toString();
    } catch (error) {
        console.error('Export error:', error);
    } finally {
        isExporting.value = false;
    }
}
</script>