<template>
    <AuthenticatedLayout title="Orders Report" description="Monitor and analyze order data across your facilities">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        Orders Report
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Monitor and analyze order data across your facilities
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="refreshData"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                        :disabled="isLoading"
                    >
                        <svg class="w-4 h-4 mr-2" :class="{'animate-spin': isLoading}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Refresh
                    </button>
                    <button
                        @click="exportCSV"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors shadow-sm"
                        :disabled="isLoading"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export CSV
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold text-gray-900">{{ summary.total_orders || 0 }}</div>
                            <div class="text-sm font-medium text-gray-500">Total Orders</div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold text-gray-900">{{ summary.pending || 0 }}</div>
                            <div class="text-sm font-medium text-gray-500">Pending</div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold text-gray-900">{{ summary.approved || 0 }}</div>
                            <div class="text-sm font-medium text-gray-500">Approved</div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold text-gray-900">{{ summary.completed || 0 }}</div>
                            <div class="text-sm font-medium text-gray-500">Completed</div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold text-gray-900">{{ summary.cancelled || 0 }}</div>
                            <div class="text-sm font-medium text-gray-500">Cancelled</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Filter & Search</h3>
                            <p class="text-sm text-gray-500 mt-1">Refine your order results</p>
                        </div>
                        <button 
                            @click="clearFilters"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Clear All Filters
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Search -->
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search Orders</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input
                                    v-model="form.search"
                                    type="text"
                                    placeholder="Search by Order Number or Notes..."
                                    class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @input="debouncedFilter"
                                />
                            </div>
                        </div>

                        <!-- Per Page -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Items per page</label>
                            <select
                                v-model="form.per_page"
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
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                        <!-- Status Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select
                                v-model="form.status"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @change="applyFilters"
                            >
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="reviewed">Reviewed</option>
                                <option value="approved">Approved</option>
                                <option value="in_process">In Process</option>
                                <option value="dispatched">Dispatched</option>
                                <option value="delivered">Delivered</option>
                                <option value="received">Received</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @change="applyFilters"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @change="applyFilters"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Orders</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ orders.total || 0 }} total orders found
                            </p>
                        </div>
                        <div class="flex items-center space-x-2 text-sm text-gray-500">
                            <span>Showing {{ orders.from || 0 }} to {{ orders.to || 0 }} of {{ orders.total || 0 }} results</span>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">Order Number</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">Order Date</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">Items</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">Created By</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">Expected Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="!orders.data || orders.data.length === 0" class="hover:bg-gray-50">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <h3 class="text-lg font-medium text-gray-900 mb-2">No orders found</h3>
                                        <p class="text-gray-500">Try adjusting your filters or search criteria.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-2 h-2 rounded-full mr-3" :class="getStatusDotClass(order.status)"></div>
                                        <Link 
                                            :href="route('orders.show', order.id)"
                                            class="text-indigo-600 hover:text-indigo-800 font-medium hover:underline transition-colors"
                                        >
                                            {{ order.order_number || 'N/A' }}
                                        </Link>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDate(order.order_date) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getStatusClass(order.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                        {{ getStatusLabel(order.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex items-center">
                                        <span class="font-medium">{{ order.items?.length || 0 }}</span>
                                        <span class="text-gray-500 ml-1">items</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                                            <span class="text-xs font-medium text-gray-600">
                                                {{ (order.user?.name || 'N/A').charAt(0).toUpperCase() }}
                                            </span>
                                        </div>
                                        <span>{{ order.user?.name || 'N/A' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDate(order.expected_date) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200">
                    <TailwindPagination 
                        :data="orders" 
                        @pagination-change-page="goToPage"
                        :limit="3"
                    />
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
    name: 'OrdersReport',
    components: {
        AuthenticatedLayout,
        TailwindPagination,
        Link
    },
    props: {
        orders: Object,
        filters: Object,
        summary: Object,
    },
    data() {
        return {
            form: {
                search: this.filters?.search || '',
                status: this.filters?.status || '',
                start_date: this.filters?.start_date || '',
                end_date: this.filters?.end_date || '',
                per_page: this.filters?.per_page || '15',
            },
            debouncedFilter: debounce(this.applyFilters, 300),
            isLoading: false,
        };
    },
    watch: {
        filters: {
            handler(newFilters) {
                if (newFilters) {
                    this.form.search = newFilters.search || '';
                    this.form.status = newFilters.status || '';
                    this.form.start_date = newFilters.start_date || '';
                    this.form.end_date = newFilters.end_date || '';
                    this.form.per_page = newFilters.per_page || '15';
                }
            },
            deep: true,
            immediate: true
        }
    },
    methods: {
        applyFilters() {
            this.isLoading = true;
            router.get(route('reports.orders'), this.form, {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => {
                    this.isLoading = false;
                }
            });
        },

        refreshData() {
            this.isLoading = true;
            router.reload({
                onFinish: () => {
                    this.isLoading = false;
                }
            });
        },

        clearFilters() {
            this.form.search = '';
            this.form.status = '';
            this.form.start_date = '';
            this.form.end_date = '';
            this.form.per_page = '15';
            
            this.applyFilters();
        },

        setQuickFilter(type) {
            const today = new Date();
            const formatDate = (date) => date.toISOString().split('T')[0];
            
            // Clear existing filters first
            this.form.search = '';
            this.form.status = '';
            this.form.start_date = '';
            this.form.end_date = '';
            
            switch (type) {
                case 'today':
                    this.form.start_date = formatDate(today);
                    this.form.end_date = formatDate(today);
                    break;
                    
                case 'this_week':
                    const startOfWeek = new Date(today.setDate(today.getDate() - today.getDay()));
                    const endOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + 6));
                    this.form.start_date = formatDate(startOfWeek);
                    this.form.end_date = formatDate(endOfWeek);
                    break;
                    
                case 'this_month':
                    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                    const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    this.form.start_date = formatDate(startOfMonth);
                    this.form.end_date = formatDate(endOfMonth);
                    break;
                    
                case 'pending_only':
                    this.form.status = 'pending';
                    break;
                    
                case 'received_only':
                    this.form.status = 'received';
                    break;
            }
            
            this.applyFilters();
        },

        goToPage(page) {
            const params = {
                ...this.form,
                page: page
            };
            
            router.get(route('reports.orders'), params, {
                preserveState: true,
                preserveScroll: true,
            });
        },

        exportCSV() {
            const params = new URLSearchParams(this.form);
            const url = route('reports.orders.export') + '?' + params.toString();
            window.open(url, '_blank');
        },

        formatDate(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString('en-GB', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        },

        getOrderTypeClass(type) {
            const classes = {
                'regular': 'bg-blue-100 text-blue-800',
                'emergency': 'bg-red-100 text-red-800',
                'routine': 'bg-green-100 text-green-800',
            };
            return classes[type] || 'bg-gray-100 text-gray-800';
        },

        getOrderTypeLabel(type) {
            const labels = {
                'regular': 'Regular',
                'emergency': 'Emergency',
                'routine': 'Routine',
            };
            return labels[type] || 'Unknown';
        },

        getStatusClass(status) {
            const classes = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'reviewed': 'bg-yellow-100 text-yellow-800',
                'approved': 'bg-green-100 text-green-800',
                'in_process': 'bg-blue-100 text-blue-800',
                'dispatched': 'bg-purple-100 text-purple-800',
                'delivered': 'bg-orange-100 text-orange-800',
                'received': 'bg-indigo-100 text-indigo-800',
                'rejected': 'bg-red-100 text-red-800',
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },

        getStatusLabel(status) {
            const labels = {
                'pending': 'Pending',
                'reviewed': 'Reviewed',
                'approved': 'Approved',
                'in_process': 'In Process',
                'dispatched': 'Dispatched',
                'delivered': 'Delivered',
                'received': 'Received',
                'rejected': 'Rejected',
            };
            return labels[status] || 'Unknown';
        },

        getTotalQuantity(order) {
            if (!order.items || order.items.length === 0) return 0;
            return order.items.reduce((total, item) => total + (item.quantity || 0), 0);
        },

        getStatusDotClass(status) {
            const classes = {
                'pending': 'bg-yellow-400',
                'reviewed': 'bg-yellow-400',
                'approved': 'bg-green-400',
                'in_process': 'bg-blue-400',
                'dispatched': 'bg-purple-400',
                'delivered': 'bg-orange-400',
                'received': 'bg-indigo-400',
                'rejected': 'bg-red-400',
            };
            return classes[status] || 'bg-gray-400';
        },
    },
};
</script>
