<template>
    <AuthenticatedLayout>
        <div class="">
            <div class="">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-800">Transfers Report</h2>
                            <button 
                                @click="exportTransfers"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>Export CSV</span>
                            </button>
                        </div>

                        <!-- Summary Cards -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6" v-if="summary">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600">{{ summary.total_transfers }}</div>
                                <div class="text-sm text-blue-800">Total Transfers</div>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-green-600">{{ summary.outgoing_transfers }}</div>
                                <div class="text-sm text-green-800">Outgoing</div>
                            </div>
                            <div class="bg-orange-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-orange-600">{{ summary.incoming_transfers }}</div>
                                <div class="text-sm text-orange-800">Incoming</div>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-purple-600">{{ summary.received }}</div>
                                <div class="text-sm text-purple-800">Received</div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="bg-gray-50 p-4 rounded-lg mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">Filter Transfers</h3>
                                <button 
                                    @click="clearFilters"
                                    class="text-gray-500 hover:text-gray-700 text-sm flex items-center space-x-1"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    <span>Clear All</span>
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <!-- Search -->
                                <div class="lg:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Transfers</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                        </div>
                                        <input
                                            v-model="filters.search"
                                            type="text"
                                            placeholder="Search by Transfer ID, Note, or Location..."
                                            class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            @input="debouncedFilter"
                                        />
                                    </div>
                                </div>

                                <!-- Per Page -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Items per page</label>
                                    <select
                                        v-model="filters.per_page"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        @change="applyFilters"
                                    >
                                        <option value="15">15 items</option>
                                        <option value="25">25 items</option>
                                        <option value="50">50 items</option>
                                        <option value="100">100 items</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Second row of filters -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                                <!-- Transfer Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Transfer Direction</label>
                                    <select
                                        v-model="filters.transfer_type"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        @change="applyFilters"
                                    >
                                        <option value="">All Directions</option>
                                        <option value="outgoing">📤 Outgoing</option>
                                        <option value="incoming">📥 Incoming</option>
                                    </select>
                                </div>

                                <!-- Status Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <select
                                        v-model="filters.status"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        @change="applyFilters"
                                    >
                                        <option value="">All Statuses</option>
                                        <option value="pending">🕐 Pending</option>
                                        <option value="approved">✅ Approved</option>
                                        <option value="dispatched">🚚 Dispatched</option>
                                        <option value="received">📦 Received</option>
                                        <option value="rejected">❌ Rejected</option>
                                        <option value="cancelled">🚫 Cancelled</option>
                                    </select>
                                </div>

                                <!-- Date Range -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                                    <input
                                        v-model="filters.start_date"
                                        type="date"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        @change="applyFilters"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                                    <input
                                        v-model="filters.end_date"
                                        type="date"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        @change="applyFilters"
                                    />
                                </div>
                            </div>

                            <!-- Quick Filter Buttons -->
                            <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-200">
                                <span class="text-sm text-gray-600 font-medium mr-2">Quick filters:</span>
                                <button 
                                    @click="setQuickFilter('today')"
                                    class="px-3 py-1 text-xs bg-blue-100 text-blue-800 rounded-full hover:bg-blue-200 transition-colors"
                                >
                                    Today
                                </button>
                                <button 
                                    @click="setQuickFilter('this_week')"
                                    class="px-3 py-1 text-xs bg-green-100 text-green-800 rounded-full hover:bg-green-200 transition-colors"
                                >
                                    This Week
                                </button>
                                <button 
                                    @click="setQuickFilter('this_month')"
                                    class="px-3 py-1 text-xs bg-purple-100 text-purple-800 rounded-full hover:bg-purple-200 transition-colors"
                                >
                                    This Month
                                </button>
                                <button 
                                    @click="setQuickFilter('pending_only')"
                                    class="px-3 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full hover:bg-yellow-200 transition-colors"
                                >
                                    Pending Only
                                </button>
                                <button 
                                    @click="setQuickFilter('received_only')"
                                    class="px-3 py-1 text-xs bg-green-100 text-green-800 rounded-full hover:bg-green-200 transition-colors"
                                >
                                    Received Only
                                </button>
                            </div>
                        </div>

                        <!-- Transfers Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transfer ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From/To</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Qty</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="transfer in transfers.data" :key="transfer.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <Link 
                                                :href="route('transfers.show', transfer.id)"
                                                class="text-blue-600 hover:text-blue-800 hover:underline font-medium"
                                            >
                                                {{ transfer.transferID || 'N/A' }}
                                            </Link>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(transfer.transfer_date) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span :class="getTransferTypeClass(transfer)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                {{ getTransferType(transfer) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="space-y-1">
                                                <div class="text-xs text-gray-400">From:</div>
                                                <div>{{ getFromLocation(transfer) }}</div>
                                                <div class="text-xs text-gray-400">To:</div>
                                                <div>{{ getToLocation(transfer) }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span :class="getStatusClass(transfer.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                {{ transfer.status?.charAt(0).toUpperCase() + transfer.status?.slice(1) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ transfer.items?.length || 0 }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ getTotalQuantity(transfer) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ transfer.created_by?.name || 'N/A' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            <TailwindPagination 
                                :data="transfers" 
                                @pagination-change-page="goToPage"
                                :limit="3"
                                :show-disabled="false"
                                class="flex justify-center"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router, Link } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { TailwindPagination } from 'laravel-vue-pagination';

export default {
    name: 'TransfersReport',
    components: {
        AuthenticatedLayout,
        TailwindPagination,
        Link
    },
    props: {
        transfers: Object,
        filters: Object,
        status_options: Array
    },
    data() {
        return {
            summary: null,
            loading: false
        };
    },
    mounted() {
        this.loadSummary();
    },
    methods: {
        debouncedFilter: debounce(function() {
            this.applyFilters();
        }, 300),

        applyFilters() {
            const params = { ...this.filters };
            
            // Clean up empty values
            Object.keys(params).forEach(key => {
                if (params[key] === '' || params[key] === null || (Array.isArray(params[key]) && params[key].length === 0)) {
                    delete params[key];
                }
            });

            router.get(route('reports.transfers'), params, {
                preserveState: true,
                preserveScroll: true,
                only: ['transfers', 'filters']
            });
            
            // Reload summary with new filters
            this.loadSummary();
        },

        clearFilters() {
            this.filters.search = '';
            this.filters.transfer_type = '';
            this.filters.status = '';
            this.filters.start_date = '';
            this.filters.end_date = '';
            this.filters.per_page = '15';
            
            this.applyFilters();
        },

        setQuickFilter(type) {
            const today = new Date();
            const formatDate = (date) => date.toISOString().split('T')[0];
            
            // Clear existing filters first
            this.filters.search = '';
            this.filters.transfer_type = '';
            this.filters.status = '';
            this.filters.start_date = '';
            this.filters.end_date = '';
            
            switch (type) {
                case 'today':
                    this.filters.start_date = formatDate(today);
                    this.filters.end_date = formatDate(today);
                    break;
                    
                case 'this_week':
                    const startOfWeek = new Date(today.setDate(today.getDate() - today.getDay()));
                    const endOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + 6));
                    this.filters.start_date = formatDate(startOfWeek);
                    this.filters.end_date = formatDate(endOfWeek);
                    break;
                    
                case 'this_month':
                    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                    const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    this.filters.start_date = formatDate(startOfMonth);
                    this.filters.end_date = formatDate(endOfMonth);
                    break;
                    
                case 'pending_only':
                    this.filters.status = 'pending';
                    break;
                    
                case 'received_only':
                    this.filters.status = 'received';
                    break;
            }
            
            this.applyFilters();
        },

        goToPage(page) {
            const params = {
                ...this.filters,
                page: page
            };
            
            router.get(route('reports.transfers'), params, {
                preserveState: true,
                preserveScroll: true
            });
        },

        loadSummary() {
            const params = { ...this.filters };
            delete params.page;
            delete params.per_page;
            
            axios.get(route('reports.transfers.summary'), { params })
                .then(response => {
                    this.summary = response.data;
                })
                .catch(error => {
                    console.error('Error loading summary:', error);
                });
        },

        exportTransfers() {
            const params = { ...this.filters };
            delete params.page;
            delete params.per_page;
            
            const queryString = new URLSearchParams(params).toString();
            const url = route('reports.transfers.export') + (queryString ? '?' + queryString : '');
            
            window.open(url, '_blank');
        },

        formatDate(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString();
        },

        getTransferType(transfer) {
            // Determine if this is incoming or outgoing based on the current facility
            // This would need to be determined from the backend context
            return transfer.from_facility_id ? 'Outgoing' : 'Incoming';
        },

        getTransferTypeClass(transfer) {
            const type = this.getTransferType(transfer);
            return type === 'Outgoing' 
                ? 'bg-red-100 text-red-800'
                : 'bg-green-100 text-green-800';
        },

        getFromLocation(transfer) {
            if (transfer.from_warehouse) {
                return transfer.from_warehouse.name + ' (Warehouse)';
            } else if (transfer.from_facility) {
                return transfer.from_facility.name + ' (Facility)';
            }
            return 'N/A';
        },

        getToLocation(transfer) {
            if (transfer.to_warehouse) {
                return transfer.to_warehouse.name + ' (Warehouse)';
            } else if (transfer.to_facility) {
                return transfer.to_facility.name + ' (Facility)';
            }
            return 'N/A';
        },

        getStatusClass(status) {
            const statusClasses = {
                pending: 'bg-yellow-100 text-yellow-800',
                approved: 'bg-blue-100 text-blue-800',
                dispatched: 'bg-purple-100 text-purple-800',
                received: 'bg-green-100 text-green-800',
                rejected: 'bg-red-100 text-red-800',
                cancelled: 'bg-gray-100 text-gray-800'
            };
            return statusClasses[status] || 'bg-gray-100 text-gray-800';
        },

        getTotalQuantity(transfer) {
            if (!transfer.items || transfer.items.length === 0) return 0;
            return transfer.items.reduce((total, item) => total + (item.quantity || 0), 0);
        }
    }
};
</script>
