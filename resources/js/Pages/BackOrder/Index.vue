<template>
    <AuthenticatedLayout title="Back Order" description="Back Order History">
        <div class="py-6" @click="handleOutsideClick">
            <!-- Header Section -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-r from-orange-400 to-red-500 rounded-xl">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Back Order History</h1>
                            <p class="text-gray-600 text-sm">Manage and track all back order activities</p>
                        </div>
                    </div>
                    <div>
                        <Link href="/backorders/manage" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Manage Back Orders
                        </Link>
                    </div>
                </div>
                </div>

            <!-- Search and Filter Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Search
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <input type="text" v-model="search" placeholder="Search by back order number, order number"
                                class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 text-sm bg-gray-50 hover:bg-white" />
                        </div>
                    </div>
                    <!-- Status Filter -->
                    <div>
                        <label for="status" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Status
                        </label>
                        <Multiselect
                        v-model="status"
                            :options="['pending', 'processing', 'completed', 'cancelled']"
                            :searchable="true"
                            :allow-empty="true"
                            :multiple="false"
                            placeholder="Filter by Status"
                            :show-labels="false"                            
                            class="text-sm"
                        />
                    </div>
                </div>
            </div>

            <!-- Per Page and Results Section -->
            <div class="flex items-center justify-end mb-6">
                <div class="w-48">
                    <select v-model="per_page" @change="filters.page = 1"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 text-sm bg-gray-50 hover:bg-white">
                        <option value="10">10 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                </div>
            </div>

            <!-- Main Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                    <thead class="bg-gradient-to-r from-indigo-50 to-purple-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border border-gray-200 rounded-tl-lg">SN</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border border-gray-200">Back Order ID</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border border-gray-200">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border border-gray-200">Source Type</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border border-gray-200">Notes & Documents</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border border-gray-200 rounded-tr-lg">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <tr v-for="(order, idx) in props.history.data" :key="order.id" class="hover:bg-indigo-50/30 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium border border-gray-200">{{ idx + 1 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap border border-gray-200">
                                <button class="text-indigo-600 hover:text-indigo-800 font-medium transition-colors duration-150" @click="openHistoryModal(order)">
                                    {{ order.back_order_number }}
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 border border-gray-200">{{ formatDate(order.back_order_date) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 border border-gray-200">
                                <span class="capitalize">{{ order.source_type }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 border border-gray-200">
                                <div v-if="order.attach_documents && order.attach_documents.length">
                                    <div class="relative">
                                        <button @click.stop="toggleDropdown(order.id)" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                            {{ order.attach_documents.length }} document(s)
                                        </button>
                                        <div v-if="openDropdowns.includes(order.id)" class="absolute z-10 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg">
                                            <div class="p-3">
                                                <div v-for="(doc, i) in order.attach_documents" :key="i" class="mb-2 last:mb-0">
                                                    <a :href="doc.path" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                        </svg>
                                                        {{ doc.name }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else>
                                    <div v-if="order.notes && order.notes.length > 50">
                                        <div class="relative">
                                            <button @click.stop="toggleDropdown(order.id)" class="text-gray-600 hover:text-gray-800">
                                                {{ order.notes.substring(0, 50) }}...
                                            </button>
                                            <div v-if="openDropdowns.includes(order.id)" class="absolute z-10 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg">
                                                <div class="p-3">
                                                    <p class="text-sm text-gray-700">{{ order.notes }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else>
                                        {{ order.notes || '-' }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap border border-gray-200">
                                <button class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg text-indigo-700 bg-indigo-100 hover:bg-indigo-200 transition-colors duration-150" @click="openHistoryModal(order)">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View History
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                <TailwindPagination 
                    :data="props.history" 
                    @pagination-change-page="getResults" 
                    :limit="3" 
                />
            </div>
        </div>

        <!-- Modal for Back Order items + History -->
        <Modal :show="showModal" max-width="5xl" @close="showModal = false">
            <div class="p-6" @click="handleModalOutsideClick">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">Back Order History for {{ selectedOrder?.back_order?.back_order_number || selectedOrder?.back_order_number }}</h2>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>

                <!-- Back order items (differences) -->
                <div v-if="selectedOrder?.differences?.length" class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">Back order items</h3>
                    <table class="min-w-full border border-gray-300 text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 text-left py-2 border border-gray-300">SN</th>
                                <th class="px-4 text-left py-2 border border-gray-300">Item</th>
                                <th class="px-4 text-left py-2 border border-gray-300">QTY</th>
                                <th class="px-4 text-left py-2 border border-gray-300">Status</th>
                                <th class="px-4 text-left py-2 border border-gray-300">Batch</th>
                                <th class="px-4 text-left py-2 border border-gray-300">Expiry</th>
                                <th class="px-4 text-left py-2 border border-gray-300">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <tr v-for="(diff, dIdx) in selectedOrder.differences" :key="diff.id">
                                <td class="px-4 py-2 border border-gray-300">{{ dIdx + 1 }}</td>
                                <td class="px-4 py-2 border border-gray-300">{{ diff.product?.name || '-' }}</td>
                                <td class="px-4 py-2 border border-gray-300">{{ diff.quantity }}</td>
                                <td class="px-4 py-2 border border-gray-300">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="statusClass(diff.status)">{{ diff.status }}</span>
                                </td>
                                <td class="px-4 py-2 border border-gray-300">{{ diff.inventoryAllocation?.batch_number || '-' }}</td>
                                <td class="px-4 py-2 border border-gray-300">{{ diff.inventoryAllocation?.expiry_date ? formatDate(diff.inventoryAllocation.expiry_date) : '-' }}</td>
                                <td class="px-4 py-2 border border-gray-300">{{ diff.notes || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Loading State for history -->
                <div v-if="isLoadingHistories" class="flex items-center justify-center py-6">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-2 text-gray-600">Loading history...</span>
                </div>

                <!-- History section -->
                <template v-if="!isLoadingHistories">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">History (actions)</h3>
                    <p v-if="(!histories.data || histories.data.length === 0) && selectedOrder?.differences?.length" class="text-sm text-gray-500 mb-4">No history records yet.</p>
                </template>

                <!-- Empty state: no items and no history -->
                <div v-if="!isLoadingHistories && (!selectedOrder?.differences?.length && (!histories.data || histories.data.length === 0))" class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No items or history found</h3>
                    <p class="mt-1 text-sm text-gray-500">No back order items or history data available for this back order.</p>
                </div>

                <!-- History Table (BackOrderHistory) -->
                <table v-else-if="!isLoadingHistories && histories.data && histories.data.length > 0" class="min-w-full border border-gray-300 text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 text-left py-2 border border-gray-300">SN</th>
                            <th class="px-4 text-left py-2 border border-gray-300">Item Name</th>
                            <th class="px-4 text-left py-2 border border-gray-300">Expiry Date</th>
                            <th class="px-4 text-left py-2 border border-gray-300">Batch Number</th>
                            <th class="px-4 text-left py-2 border border-gray-300">QTY</th>
                            <th class="px-4 text-left py-2 border border-gray-300">Status</th>
                            <th class="px-4 text-left py-2 border border-gray-300">Note & Attachment</th>
                            <th class="px-4 text-left py-2 border border-gray-300">Performed By</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <tr v-for="(history, idx) in histories.data" :key="history.id">
                            <td class="px-4 text-left py-2 border border-gray-300">{{ idx + 1 }}</td>
                            <td class="px-4 text-left py-2 border border-gray-300">{{ history.product?.name }}</td>
                            <td class="px-4 text-left py-2 border border-gray-300">{{ formatDate(history.expiry_date) }}</td>
                            <td class="px-4 text-left py-2 border border-gray-300">{{ history.batch_number }}</td>
                            <td class="px-4 text-left py-2 border border-gray-300">{{ history.quantity }}</td>
                            <td class="px-4 text-left py-2 border border-gray-300">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="statusClass(history.status)">
                                    {{ history.status }}
                                </span>
                            </td>
                            <td class="px-4 text-left py-2 border border-gray-300">
                                <div v-if="history.attach_documents && history.attach_documents.length">
                                    <div class="relative">
                                        <button @click.stop="toggleHistoryDropdown(history.id)" class="text-blue-600 underline">
                                            {{ history.attach_documents.length }} document(s)
                                        </button>
                                        <div v-if="openHistoryDropdowns.includes(history.id)" class="absolute z-10 mt-1 w-64 bg-white border border-gray-300 rounded-md shadow-lg">
                                            <div class="p-2">
                                                <div v-for="(doc, i) in history.attach_documents" :key="i" class="mb-1">
                                                    <a :href="doc.path" target="_blank" class="text-blue-600 underline block text-xs">{{ doc.name }}</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else>
                                    <div v-if="history.note && history.note.length > 50">
                                        <div class="relative">
                                            <button @click.stop="toggleHistoryDropdown(history.id)" class="text-gray-600">
                                                {{ history.note.substring(0, 50) }}...
                                        </button>
                                            <div v-if="openHistoryDropdowns.includes(history.id)" class="absolute z-10 mt-1 w-64 bg-white border border-gray-300 rounded-md shadow-lg">
                                                <div class="p-2">
                                                    <p class="text-xs">{{ history.note }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else>
                                        {{ history.note || '-' }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 text-left py-2 border border-gray-300">
                                {{ history.performer?.name || '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { TailwindPagination } from 'laravel-vue-pagination';
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";
import "@/Components/multiselect.css";
import { XMarkIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    history: {
        type: Object,
        required: true,
    },
    filters: Object,
});

const histories = ref([]);
const showModal = ref(false);
const selectedOrder = ref(null);
const openDropdowns = ref([]);
const openHistoryDropdowns = ref([]);
const isLoadingHistories = ref(false);

// Filter refs
const search = ref(props.filters.search);
const status = ref(props.filters.status);
const per_page = ref(props.filters.per_page || 25);

// Watch for filter changes
watch([
        () => search.value,
        () => status.value,
    () => per_page.value,
    () => props.filters.page
], () => {
    reloadBackOrders();
});

function getResults(page = 1) {
    props.filters.page = page;
}

function reloadBackOrders() {
    const query = {};
    if (search.value) query.search = search.value;
    if (status.value) query.status = status.value;
    if (per_page.value) query.per_page = per_page.value;
    if (props.filters.page) query.page = props.filters.page;

    router.get(route('backorders.index'), query, {
        preserveScroll: true,
        only: ['history', 'filters']
    });
}

function openHistoryModal(order) {
    selectedOrder.value = order;
    showModal.value = true;
    isLoadingHistories.value = true;
    fetchHistories(order.id);
}

async function fetchHistories(backOrderId) {
    isLoadingHistories.value = true;
    await axios.get(route('backorders.histories', backOrderId))
    .then(response => {
        isLoadingHistories.value = false;
        histories.value = { data: response.data };
    })
    .catch(error => {
        isLoadingHistories.value = false;
        console.error('Error fetching back order histories:', error);
        histories.value = { data: [] };
    });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString();
}

function getSourceTypeDisplay(order) {
    console.log(order);
    if (order.source_type === 'order' && order.order) {
        return `Order: ${order.order.order_number}`;
    } else if (order.source_type === 'transfer' && order.transfer) {
        return `Transfer: ${order.transfer.transfer_id || order.transfer.transferID || 'N/A'}`;
    } else {
        return order.source_type ? `${order.source_type.charAt(0).toUpperCase() + order.source_type.slice(1)}` : 'N/A';
    }
}

function statusClass(status) {
    switch (status) {
        case 'pending':
        case 'Pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'processing':
        case 'Processing':
            return 'bg-blue-100 text-blue-800';
        case 'completed':
        case 'Received':
            return 'bg-green-100 text-green-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

function toggleDropdown(id) {
    if (openDropdowns.value.includes(id)) {
        openDropdowns.value = openDropdowns.value.filter((i) => i !== id);
    } else {
        openDropdowns.value.push(id);
    }
}

function toggleHistoryDropdown(id) {
    if (openHistoryDropdowns.value.includes(id)) {
        openHistoryDropdowns.value = openHistoryDropdowns.value.filter((i) => i !== id);
    } else {
        openHistoryDropdowns.value.push(id);
    }
}

function handleOutsideClick() {
    // Close all main table dropdowns when clicking outside
    openDropdowns.value = [];
}

function handleModalOutsideClick() {
    // Close all modal dropdowns when clicking outside
    openHistoryDropdowns.value = [];
}
</script>
