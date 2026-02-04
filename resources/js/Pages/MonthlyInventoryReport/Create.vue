<template>
    <AuthenticatedLayout 
        title="Create Monthly Report"
        description="Create a new monthly inventory report with opening balances, stock movements, and adjustments"
        img="/assets/images/report.png"
    >
        <Head :title="`Monthly Report - ${monthName} ${year}`" />
        
        <div class="mb-[80px]">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="text-gray-900">
                    <!-- Header -->
                    <div class="px-4 py-3 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">Monthly Inventory Report</h2>
                                <p class="text-sm text-gray-600">{{ facility.name }} - {{ monthName }} {{ year }}</p>
                            </div>
                            <div class="flex space-x-2">
                                <Link 
                                    :href="route('reports.monthly-reports.index')"
                                    class="inline-flex items-center px-3 py-1 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-600 focus:bg-gray-600 active:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Back to Reports
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Period Selection -->
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                        <div class="flex items-center space-x-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Year</label>
                                <select v-model="selectedYear" @change="updatePeriod" class="text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Month</label>
                                <select v-model="selectedMonth" @change="updatePeriod" class="text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="(name, num) in monthNames" :key="num" :value="parseInt(num)">{{ name }}</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button @click="loadReportData" class="px-3 py-1 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Load Period
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Report Form -->
                    <form @submit.prevent="saveReports" class="p-4">
                        <!-- Action Buttons -->
                        <div class="flex justify-between items-center mb-4">
                            <div class="text-sm text-gray-600">
                                {{ reportData.length }} items to report
                            </div>
                            <div class="flex space-x-2">
                                <button 
                                    type="button"
                                    @click="calculateAllClosingBalances"
                                    class="inline-flex items-center px-3 py-1 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-800 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    Calculate All
                                </button>
                                <button 
                                    type="submit"
                                    :disabled="isSaving"
                                    class="inline-flex items-center px-3 py-1 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                >
                                    <svg v-if="!isSaving" class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <svg v-else class="animate-spin w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ isSaving ? 'Saving...' : 'Save Reports' }}
                                </button>
                            </div>
                        </div>

                        <!-- Reports Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opening Balance</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Received</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Issued</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Positive Adj.</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Negative Adj.</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Closing Balance</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stockout Days</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comments</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(item, index) in reportData" :key="item.product_id" class="hover:bg-gray-50">
                                        <!-- Item Info -->
                                        <td class="px-3 py-2 text-xs">
                                            <div class="font-medium text-gray-900">{{ item.product.name }}</div>
                                            <div class="text-gray-500" v-if="item.product.strength">{{ item.product.strength }}</div>
                                            <div class="text-gray-400" v-if="item.product.dosage_form">{{ item.product.dosage_form }}</div>
                                        </td>
                                        
                                        <!-- Opening Balance -->
                                        <td class="px-3 py-2">
                                            <input 
                                                v-model.number="item.opening_balance"
                                                @input="calculateClosingBalance(index)"
                                                type="number" 
                                                step="0.01" 
                                                min="0"
                                                class="w-20 text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </td>
                                        
                                        <!-- Stock Received -->
                                        <td class="px-3 py-2">
                                            <input 
                                                v-model.number="item.stock_received"
                                                @input="calculateClosingBalance(index)"
                                                type="number" 
                                                step="0.01" 
                                                min="0"
                                                class="w-20 text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </td>
                                        
                                        <!-- Stock Issued -->
                                        <td class="px-3 py-2">
                                            <input 
                                                v-model.number="item.stock_issued"
                                                @input="calculateClosingBalance(index)"
                                                type="number" 
                                                step="0.01" 
                                                min="0"
                                                class="w-20 text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </td>
                                        
                                        <!-- Positive Adjustments -->
                                        <td class="px-3 py-2">
                                            <input 
                                                v-model.number="item.positive_adjustments"
                                                @input="calculateClosingBalance(index)"
                                                type="number" 
                                                step="0.01" 
                                                min="0"
                                                class="w-20 text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </td>
                                        
                                        <!-- Negative Adjustments -->
                                        <td class="px-3 py-2">
                                            <input 
                                                v-model.number="item.negative_adjustments"
                                                @input="calculateClosingBalance(index)"
                                                type="number" 
                                                step="0.01" 
                                                min="0"
                                                class="w-20 text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </td>
                                        
                                        <!-- Closing Balance (Calculated) -->
                                        <td class="px-3 py-2">
                                            <div class="w-20 text-xs font-medium text-gray-900 bg-gray-100 px-2 py-1 rounded">
                                                {{ formatNumber(item.closing_balance) }}
                                            </div>
                                        </td>
                                        
                                        <!-- Stockout Days -->
                                        <td class="px-3 py-2">
                                            <input 
                                                v-model.number="item.stockout_days"
                                                type="number" 
                                                min="0" 
                                                max="31"
                                                class="w-16 text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </td>
                                        
                                        <!-- Comments -->
                                        <td class="px-3 py-2">
                                            <input 
                                                v-model="item.comments"
                                                type="text" 
                                                placeholder="Optional comments"
                                                class="w-32 text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary -->
                        <div class="mt-6 bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-900 mb-3">Report Summary</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="text-center">
                                    <div class="text-lg font-bold text-gray-900">{{ formatNumber(totalOpeningBalance) }}</div>
                                    <div class="text-xs text-gray-500">Total Opening Balance</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-green-600">{{ formatNumber(totalReceived) }}</div>
                                    <div class="text-xs text-gray-500">Total Received</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-red-600">{{ formatNumber(totalIssued) }}</div>
                                    <div class="text-xs text-gray-500">Total Issued</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-gray-900">{{ formatNumber(totalClosingBalance) }}</div>
                                    <div class="text-xs text-gray-500">Total Closing Balance</div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import Swal from 'sweetalert2';

// Debounce utility
function debounce(func, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
}

const props = defineProps({
    reportData: Array,
    year: [Number, String],
    month: [Number, String],
    monthName: String,
    facility: Object,
});

// Reactive state
const isSaving = ref(false);
const selectedYear = ref(parseInt(props.year));
const selectedMonth = ref(parseInt(props.month));
const reportData = ref(JSON.parse(JSON.stringify(props.reportData))); // Deep copy

const monthNames = {
    1: 'January', 2: 'February', 3: 'March', 4: 'April',
    5: 'May', 6: 'June', 7: 'July', 8: 'August',
    9: 'September', 10: 'October', 11: 'November', 12: 'December'
};

// Generate available years
function generateYears() {
    const currentYear = new Date().getFullYear();
    const years = [];
    for (let year = currentYear - 2; year <= currentYear + 1; year++) {
        years.push(year);
    }
    return years;
}

const availableYears = generateYears();

// Computed properties
const totalOpeningBalance = computed(() => {
    return reportData.value.reduce((sum, item) => sum + (parseFloat(item.opening_balance) || 0), 0);
});

const totalReceived = computed(() => {
    return reportData.value.reduce((sum, item) => sum + (parseFloat(item.stock_received) || 0), 0);
});

const totalIssued = computed(() => {
    return reportData.value.reduce((sum, item) => sum + (parseFloat(item.stock_issued) || 0), 0);
});

const totalClosingBalance = computed(() => {
    return reportData.value.reduce((sum, item) => sum + (parseFloat(item.closing_balance) || 0), 0);
});

// Methods
function updatePeriod() {
    try {
        router.get(route('reports.monthly-reports.create'), {
            year: selectedYear.value,
            month: selectedMonth.value
        });
    } catch (error) {
        console.error('Navigation error:', error);
        Swal.fire({
            title: 'Navigation Error',
            text: 'Failed to load new period. Please try again.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    }
}

function loadReportData() {
    updatePeriod();
}

function calculateClosingBalance(index) {
    try {
        const item = reportData.value[index];
        const opening = parseFloat(item.opening_balance) || 0;
        const received = parseFloat(item.stock_received) || 0;
        const issued = parseFloat(item.stock_issued) || 0;
        const positiveAdj = parseFloat(item.positive_adjustments) || 0;
        const negativeAdj = parseFloat(item.negative_adjustments) || 0;
        
        // Calculate closing balance without validation during calculation
        // Validation will happen during save
        item.closing_balance = opening + received - issued + positiveAdj - negativeAdj;
    } catch (error) {
        console.error('Calculation error:', error);
        // Don't show SweetAlert during bulk calculations to avoid spam
        console.warn('Failed to calculate closing balance for item:', item);
    }
}

function calculateAllClosingBalances() {
    try {
        let calculatedCount = 0;
        reportData.value.forEach((item, index) => {
            calculateClosingBalance(index);
            calculatedCount++;
        });
        
        // Show success message
        Swal.fire({
            title: 'Calculation Complete',
            text: `Successfully calculated closing balances for ${calculatedCount} items`,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    } catch (error) {
        console.error('Bulk calculation error:', error);
        Swal.fire({
            title: 'Calculation Error',
            text: 'Failed to calculate all closing balances. Please try again.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    }
}

function validateReports() {
    for (const item of reportData.value) {
        // Check for negative values
        if (item.opening_balance < 0 || item.stock_received < 0 || 
            item.stock_issued < 0 || item.positive_adjustments < 0 || 
            item.negative_adjustments < 0 || item.stockout_days < 0) {
            return { valid: false, message: 'All values must be greater than or equal to 0' };
        }
        

    }
    return { valid: true };
}

// Create debounced version of the save function
const debouncedSaveReports = debounce(async () => {
    // Validate before saving
    const validation = validateReports();
    if (!validation.valid) {
        Swal.fire({
            title: 'Validation Error',
            text: validation.message,
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }

    isSaving.value = true;
    
    try {
        // Prepare data for submission
        const reportsToSave = reportData.value.map(item => ({
            product_id: item.product_id,
            opening_balance: parseFloat(item.opening_balance) || 0,
            stock_received: parseFloat(item.stock_received) || 0,
            stock_issued: parseFloat(item.stock_issued) || 0,
            positive_adjustments: parseFloat(item.positive_adjustments) || 0,
            negative_adjustments: parseFloat(item.negative_adjustments) || 0,
            stockout_days: parseInt(item.stockout_days) || 0,
            comments: item.comments || '',
        }));
        
        router.post(route('reports.monthly-reports.store'), {
            year: selectedYear.value,
            month: selectedMonth.value,
            reports: reportsToSave,
        }, {
            onSuccess: () => {
                isSaving.value = false;
                Swal.fire({
                    title: 'Success',
                    text: 'Monthly reports saved successfully',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            },
            onError: (errors) => {
                isSaving.value = false;
                console.error('Save errors:', errors);
                Swal.fire({
                    title: 'Save Failed',
                    text: 'Failed to save reports. Please check your data and try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    } catch (error) {
        isSaving.value = false;
        console.error('Save error:', error);
        Swal.fire({
            title: 'Unexpected Error',
            text: 'An unexpected error occurred. Please try again.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    }
}, 500);

function saveReports() {
    debouncedSaveReports();
}

function formatNumber(value) {
    return parseFloat(value || 0).toLocaleString();
}

// Initialize on mount
onMounted(() => {
    calculateAllClosingBalances();
});
</script>
