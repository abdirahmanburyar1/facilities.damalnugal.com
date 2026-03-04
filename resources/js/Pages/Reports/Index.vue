<template>
    <Head title="Reports" />
    <AuthenticatedLayout
        title="Reports"
        description="Generate and view all facility reports"
        img="/assets/images/report.png"
    >
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Reports
            </h2>
        </template>

        <div class="py-5">
            <!-- Filters -->
            <div class="bg-blue-50/90 border border-blue-200 rounded-lg shadow-sm p-6 mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Report Type</label>
                        <select
                            v-model="filters.report_type"
                            class="mt-1 block w-full rounded-md border border-gray-300 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2"
                        >
                            <option value="">Report Type</option>
                            <option v-for="rt in reportTypes" :key="rt.value" :value="rt.value">{{ rt.label }}</option>
                        </select>
                    </div>

                    <!-- Only show date range for non-monthly reports -->
                    <template v-if="filters.report_type && filters.report_type !== 'facility_monthly_consumption'">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input
                                type="date"
                                v-model="filters.start_date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input
                                type="date"
                                v-model="filters.end_date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2"
                            />
                        </div>
                    </template>

                    <!-- Monthly Report Filters -->
                    <div v-if="filters.report_type === 'facility_monthly_consumption'" class="flex flex-col gap-4 col-span-2">
                        <div class="flex flex-row gap-3 items-end flex-wrap">
                            <div class="flex-1 min-w-[100px]">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                                <select
                                    v-model="filters.year"
                                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2"
                                >
                                    <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                                </select>
                            </div>
                            <div class="flex-1 min-w-[100px]">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                                <select
                                    v-model="filters.month"
                                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2"
                                >
                                    <option
                                        v-for="(opt, index) in months"
                                        :key="index"
                                        :value="index + 1"
                                    >
                                        {{ opt }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button
                        type="button"
                        @click="generateReport"
                        :disabled="generating || !filters.report_type"
                        class="inline-flex justify-center items-center px-6 py-2.5 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150"
                    >
                        <span v-if="generating">Generating...</span>
                        <span v-else>Generate Report</span>
                    </button>
                    <p v-if="!filters.report_type" class="mt-2 text-xs text-amber-600">
                        Select a report type to continue.
                    </p>
                </div>
            </div>

            <!-- Search (For Transfers and Orders) -->
            <div v-if="filters.report_type === 'transfer_report' || filters.report_type === 'order_report'" class="mb-4">
                <label class="sr-only">Search</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="filters.search"
                        @input="debounceGenerate"
                        type="text"
                        class="block w-full rounded-md border-gray-300 pl-10 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        placeholder="Search ID or notes"
                    />
                </div>
            </div>

            <!-- Report Table Content -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 min-h-[400px]">
                <div v-if="generating" class="p-8 text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600">Loading report...</p>
                </div>
                
                <div v-else-if="hasGenerated && !hasData" class="p-8 text-center text-gray-600 max-w-xl mx-auto">
                    <div class="text-gray-400 mb-4">
                        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-gray-900 mb-2">{{ reportMessage }}</h3>
                </div>

                <!-- LMIS Monthly Report -->
                <div v-else-if="currentData && currentReportType === 'facility_monthly_consumption'">
                    <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">LMIS Monthly Report</h3>
                            <p class="text-sm text-gray-500">Period: {{ currentData.report_period }} | Status: <span class="font-semibold uppercase">{{ currentData.status }}</span></p>
                        </div>
                        <button @click="exportLMIS" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md text-sm">
                            Export Excel
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border border-gray-300">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">Item</th>
                                    <th class="px-3 py-2 text-right text-xs font-bold text-gray-700 border border-gray-300">Beginning Balance</th>
                                    <th class="px-3 py-2 text-right text-xs font-bold text-gray-700 border border-gray-300">Qty Received</th>
                                    <th class="px-3 py-2 text-right text-xs font-bold text-gray-700 border border-gray-300">Qty Consumed</th>
                                    <th class="px-3 py-2 text-right text-xs font-bold text-gray-700 border border-gray-300 bg-yellow-50">Positive Adj.</th>
                                    <th class="px-3 py-2 text-right text-xs font-bold text-gray-700 border border-gray-300 bg-yellow-50">Negative Adj.</th>
                                    <th class="px-3 py-2 text-right text-xs font-bold text-gray-700 border border-gray-300 bg-blue-50">Closing Balance</th>
                                    <th class="px-3 py-2 text-center text-xs font-bold text-gray-700 border border-gray-300 bg-yellow-50">Stockout Days</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <tr v-for="item in currentData.items" :key="item.id" class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-sm text-gray-900 border border-gray-300">{{ item.product?.name }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900 text-right border border-gray-300">{{ formatNum(item.opening_balance) }}</td>
                                    <td class="px-3 py-2 text-sm text-green-600 text-right border border-gray-300">{{ formatNum(item.stock_received) }}</td>
                                    <td class="px-3 py-2 text-sm text-red-600 text-right border border-gray-300">{{ formatNum(item.stock_issued) }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900 text-right border border-gray-300 bg-yellow-50">{{ formatNum(item.positive_adjustments) }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900 text-right border border-gray-300 bg-yellow-50">{{ formatNum(item.negative_adjustments) }}</td>
                                    <td class="px-3 py-2 text-sm font-semibold text-gray-900 text-right border border-gray-300 bg-blue-50" :class="item.closing_balance <= 0 ? 'text-red-600' : ''">{{ formatNum(item.closing_balance) }}</td>
                                    <td class="px-3 py-2 text-sm text-orange-600 text-center border border-gray-300 bg-yellow-50">{{ item.stockout_days || 0 }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Transfers Report -->
                <div v-else-if="currentData && currentReportType === 'transfer_report'">
                    <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Transfers Report</h3>
                        <button @click="exportCSV('/reports/transfers/export')" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md text-sm">
                            Export CSV
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border border-gray-300">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">Transfer ID</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">Date</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">Status</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">From</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">To</th>
                                    <th class="px-3 py-2 text-center text-xs font-bold text-gray-700 border border-gray-300">Items Count</th>
                                    <th class="px-3 py-2 text-right text-xs font-bold text-gray-700 border border-gray-300">Total Qty</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <tr v-for="transfer in currentData.data" :key="transfer.id" class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-sm font-medium text-blue-600 border border-gray-300">
                                        <Link :href="`/transfers/${transfer.id}/show`">{{ transfer.transferID }}</Link>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-900 border border-gray-300">{{ formatDate(transfer.transfer_date) }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900 border border-gray-300 capitalize">
                                        <span class="px-2 py-1 text-xs rounded-full" :class="getStatusColor(transfer.status)">{{ transfer.status }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-900 border border-gray-300">{{ transfer.from_warehouse?.name || transfer.from_facility?.name }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900 border border-gray-300">{{ transfer.to_warehouse?.name || transfer.to_facility?.name }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900 text-center border border-gray-300">{{ transfer.items?.length || 0 }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900 text-right border border-gray-300">{{ formatNum(transfer.items?.reduce((sum, item) => sum + item.quantity, 0)) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Orders Report -->
                <div v-else-if="currentData && currentReportType === 'order_report'">
                    <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Orders Report</h3>
                        <button @click="exportCSV('/reports/orders/export')" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md text-sm">
                            Export CSV
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border border-gray-300">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">Order Number</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">Date</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">Type</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">Status</th>
                                    <th class="px-3 py-2 text-center text-xs font-bold text-gray-700 border border-gray-300">Items Count</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">Expected Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <tr v-for="order in currentData.data" :key="order.id" class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-sm font-medium text-blue-600 border border-gray-300">
                                        <Link :href="`/orders/${order.id}/show`">{{ order.order_number }}</Link>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-900 border border-gray-300">{{ formatDate(order.order_date) }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900 border border-gray-300 capitalize">{{ order.order_type }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900 border border-gray-300 capitalize">
                                        <span class="px-2 py-1 text-xs rounded-full" :class="getStatusColor(order.status)">{{ order.status }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-900 text-center border border-gray-300">{{ order.items?.length || 0 }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900 border border-gray-300">{{ formatDate(order.expected_date) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Inventory Movements Report -->
                <div v-else-if="currentData && currentReportType === 'inventory_movements'">
                    <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Inventory Movements Report</h3>
                        <button @click="exportCSV('/reports/inventory-movements/export')" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md text-sm">
                            Export CSV
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border border-gray-300">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">Date</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">Product</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">Type</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">Source</th>
                                    <th class="px-3 py-2 text-right text-xs font-bold text-gray-700 border border-gray-300">Quantity</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">Batch/Expiry</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <tr v-for="movement in currentData.data" :key="movement.id" class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-sm text-gray-900 border border-gray-300">{{ formatDate(movement.movement_date) }}</td>
                                    <td class="px-3 py-2 text-sm font-medium text-gray-900 border border-gray-300">{{ movement.product?.name }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900 border border-gray-300">
                                        <span class="px-2 py-1 text-xs rounded-full" :class="movement.movement_type === 'facility_received' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                            {{ movement.movement_type === 'facility_received' ? 'Received' : 'Issued' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-900 border border-gray-300 capitalize">{{ movement.source_type }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900 text-right border border-gray-300">{{ formatNum(movement.quantity) }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900 border border-gray-300">
                                        <div v-if="movement.batch_number">{{ movement.batch_number }}</div>
                                        <div v-if="movement.expiry_date" class="text-xs text-gray-500">{{ formatDate(movement.expiry_date) }}</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination for paginated tables -->
                <div v-if="hasData && currentData.links" class="p-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing {{ currentData.from || 0 }} to {{ currentData.to || 0 }} of {{ currentData.total || 0 }} entries
                        </div>
                        <div class="flex gap-1">
                            <button 
                                v-for="(link, i) in currentData.links" 
                                :key="i"
                                @click="link.url && loadPage(link.url)"
                                :disabled="!link.url"
                                class="px-3 py-1 text-sm border rounded"
                                :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 disabled:opacity-50'"
                                v-html="link.label"
                            ></button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import * as XLSX from 'xlsx'

const props = defineProps({
    reportTypes: Array,
    reportPeriodOptions: Array,
})

const toast = useToast()

const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
]

const currentYear = new Date().getFullYear()
const currentMonth = new Date().getMonth() + 1
const yearOptions = Array.from({ length: 6 }, (_, i) => currentYear - i)

const filters = ref({
    report_type: '',
    start_date: '',
    end_date: '',
    search: '',
    year: currentYear,
    month: currentMonth
})

const generating = ref(false)
const hasGenerated = ref(false)
const currentReportType = ref('')
const currentData = ref(null)
const reportMessage = ref('No data found for the selected filters.')

let searchTimeout = null
const debounceGenerate = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        if (hasGenerated.value) {
            generateReport()
        }
    }, 500)
}

const hasData = computed(() => {
    if (!currentData.value) return false
    if (currentReportType.value === 'facility_monthly_consumption') {
        return currentData.value.items && currentData.value.items.length > 0
    }
    return currentData.value.data && currentData.value.data.length > 0
})

const generateReport = async () => {
    generating.value = true
    hasGenerated.value = true
    currentReportType.value = filters.value.report_type
    
    try {
        const response = await axios.post('/reports/unified-data', filters.value)
        currentData.value = response.data.data
        if (!hasData.value) {
            reportMessage.value = response.data.message || 'No data found for the selected filters.'
        }
    } catch (error) {
        console.error('Error fetching report data:', error)
        toast.error('An error occurred while generating the report.')
        currentData.value = null
    } finally {
        generating.value = false
    }
}

const loadPage = async (url) => {
    if (!url) return
    generating.value = true
    try {
        const params = new URLSearchParams(new URL(url).search)
        const response = await axios.post('/reports/unified-data', {
            ...filters.value,
            page: params.get('page')
        })
        currentData.value = response.data.data
    } catch (error) {
        console.error('Error fetching page:', error)
    } finally {
        generating.value = false
    }
}

const exportCSV = (exportUrl) => {
    const params = new URLSearchParams()
    if (filters.value.start_date) params.append('start_date', filters.value.start_date)
    if (filters.value.end_date) params.append('end_date', filters.value.end_date)
    if (filters.value.search) params.append('search', filters.value.search)
    
    window.location.href = `${exportUrl}?${params.toString()}`
}

const exportLMIS = () => {
    if (!currentData.value || !currentData.value.items) return

    const excelData = []
    excelData.push([`LMIS Monthly Report - Period: ${currentData.value.report_period}`])
    excelData.push([])
    excelData.push([
        'Item',
        'Beginning Balance',
        'Qty Received',
        'Qty Consumed',
        'Positive Adjustment',
        'Negative Adjustment',
        'Closing Balance',
        'Stockout Days'
    ])

    currentData.value.items.forEach(item => {
        excelData.push([
            item.product?.name || '',
            item.opening_balance || 0,
            item.stock_received || 0,
            item.stock_issued || 0,
            item.positive_adjustments || 0,
            item.negative_adjustments || 0,
            item.closing_balance || 0,
            item.stockout_days || 0
        ])
    })

    const ws = XLSX.utils.aoa_to_sheet(excelData)
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'Monthly Report')
    XLSX.writeFile(wb, `LMIS_Report_${currentData.value.report_period}.xlsx`)
}

const formatNum = (val) => {
    if (val === null || val === undefined) return '0'
    return Number(val).toLocaleString()
}

const formatDate = (dateString) => {
    if (!dateString) return '-'
    const date = new Date(dateString)
    return date.toLocaleDateString('en-GB')
}

const getStatusColor = (status) => {
    const st = String(status).toLowerCase()
    if (['approved', 'completed', 'received', 'delivered'].includes(st)) return 'bg-green-100 text-green-800'
    if (['pending', 'draft'].includes(st)) return 'bg-yellow-100 text-yellow-800'
    if (['rejected', 'cancelled'].includes(st)) return 'bg-red-100 text-red-800'
    return 'bg-gray-100 text-gray-800'
}
</script>
