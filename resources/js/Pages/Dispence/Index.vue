<template>
    <AuthenticatedLayout title="Patient Dispensing" description="Manage patient medication dispensing records"
        img="/assets/images/dispence.png">
        
        <!-- Header Section -->
        <div class="flex flex-col space-y-6 mb-[80px]">
            <!-- Action Buttons -->
            <div class="flex items-center justify-end space-x-3">
                <button @click="router.visit(route('moh-dispense.index'))"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-lg transition-all duration-200 shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    MOH Dispense
                </button>
                <button @click="router.visit(route('dispence.create'))"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-lg transition-all duration-200 shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    New Dispense
                </button>
            </div>

            <!-- Filters Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Search -->
                    <div class="relative">
                        <input type="text" v-model="search"
                            class="pl-10 pr-4 py-2 border border-gray-300 w-full rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            placeholder="Search patient name or phone" />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                        <div class="w-full sm:w-auto">
                            <input type="date" v-model="date_from"
                                class="border border-gray-300 w-full rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                @change="date_to = null"
                                placeholder="From Date" />
                        </div>
                        <span class="text-gray-500 text-center sm:text-left">to</span>
                        <div class="w-full sm:w-auto">
                            <input type="date" v-model="date_to"
                                class="border border-gray-300 w-full rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                :min="date_from"
                                placeholder="To Date" />
                        </div>
                    </div>

                    <!-- Per Page -->
                    <div>
                        <select v-model="per_page" 
                        @change="props.filters.page = 1"
                            class="border border-gray-300 w-full rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                            <option value="10">10 per page</option>
                            <option value="25">25 per page</option>
                            <option value="50">50 per page</option>
                            <option value="100">100 per page</option>
                        </select>
                    </div>
                </div>

                <!-- Clear Filters Button -->
                <div class="flex justify-end mt-4">
                    <button @click="clearFilters"
                        class="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-all duration-200">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">Total Dispences</p>
                            <p class="text-2xl font-bold">{{ props.dispences.total || 0 }}</p>
                        </div>
                        <div class="bg-blue-400 rounded-lg p-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium">Today's Dispences</p>
                            <p class="text-2xl font-bold">{{ todayDispencesCount }}</p>
                        </div>
                        <div class="bg-green-400 rounded-lg p-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm font-medium">Total Items Dispensed</p>
                            <p class="text-2xl font-bold">{{ totalItemsDispensed }}</p>
                        </div>
                        <div class="bg-purple-400 rounded-lg p-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dispences Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <template v-if="props.dispences.data.length > 0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Dispence Number
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Patient Information
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Items
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Dispensed By
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="dispence in props.dispences.data" :key="dispence.id" 
                                    class="hover:bg-gray-50 transition-colors duration-200"
                                    :class="{ 'bg-green-50 border-l-4 border-l-green-500': isToday(dispence.dispence_date) }">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link :href="route('dispence.show', dispence.id)" 
                                            class="text-blue-600 hover:text-blue-900 font-medium">
                                            {{ dispence.dispence_number }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span class="text-sm text-gray-900">{{ moment(dispence.dispence_date).format('DD/MM/YYYY') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ dispence.patient_name }}</div>
                                            <div class="text-sm text-gray-500">{{ dispence.patient_phone }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ dispence.items_count }} items
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ dispence.dispenced_by?.name || 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <Link :href="route('dispence.show', dispence.id)" 
                                            class="text-blue-600 hover:text-blue-900 mr-3">
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
                
                <template v-else>
                    <div class="text-center py-12">
                        <div class="mx-auto h-24 w-24 text-gray-300 mb-4">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No dispences found</h3>
                        <p class="text-gray-500 mb-6">
                            {{ search ? 'No results match your search criteria.' : 'Get started by creating a new dispence record.' }}
                        </p>
                        <div v-if="!search">
                            <button @click="router.visit(route('dispence.create'))"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                Create New Dispence
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Pagination -->
            <div class="flex justify-end mt-2 mb-[20px]">
                <TailwindPagination :data="props.dispences" @pagination-change-page="getResults" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import { TailwindPagination } from "laravel-vue-pagination";
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import moment from 'moment';

const props = defineProps({
    dispences: Object,
    filters: Object,
})

// Reactive data
const per_page = ref(props.filters.per_page || 10);
const search = ref(props.filters.search || '');
const date_from = ref(props.filters.date_from || '');
const date_to = ref(props.filters.date_to || '');

const today = moment().format('YYYY-MM-DD');

// Computed properties
const todayDispencesCount = computed(() => {
    return props.dispences.data.filter(dispence => isToday(dispence.dispence_date)).length;
});

const totalItemsDispensed = computed(() => {
    return props.dispences.data.reduce((total, dispence) => total + (dispence.items_count || 0), 0);
});

// Methods
const isToday = (dateString) => {
    if (!dateString) return false;
    return moment(dateString).format('YYYY-MM-DD') === today;
};

const clearFilters = () => {
    search.value = '';
    date_from.value = '';
    date_to.value = '';
    per_page.value = 10;
    reloadDispences();
};

const reloadDispences = () => {
    const query = {};
    if (search.value) query.search = search.value;
    if (date_from.value) query.date_from = date_from.value;
    if (date_to.value) query.date_to = date_to.value;
    if (per_page.value) {
        query.per_page = per_page.value;
    }
    if (props.filters.page) query.page = props.filters.page;
    
    router.get(route('dispence.index'), query, { 
        preserveState: true, 
        preserveScroll: true, 
        only: ['dispences'] 
    });
};

const getResults = (page = 1) => {
    props.filters.page = page;
};

// Watchers
watch([
  () => search.value, 
  () => per_page.value, 
  () => date_from.value, 
  () => date_to.value 
], () => {
    reloadDispences();
}, { deep: true });

watch(() => props.filters.page, () => {
    reloadDispences();
});
</script>
