<template>
    <AuthenticatedLayout 
        title="Monthly Inventory Report"
        description="View and analyze monthly inventory reports with detailed stock movements and balances"
        img="/assets/images/report.png"
    >
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Monthly Inventory Report
            </h2>
        </div>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8">
                        
                        <!-- Current Facility Info -->
                        <div class="mb-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <h3 class="text-lg font-semibold text-blue-900 mb-2">Current Facility</h3>
                            <p class="text-blue-800">{{ facility.name }}</p>
                            <p class="text-sm text-blue-600">{{ facility.facility_type }}</p>
                        </div>

                        <!-- Report Generation Form -->
                        <form @submit.prevent="generateReport" class="mb-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                
                                <!-- Year Selection -->
                                <div>
                                    <label for="year" class="block text-sm font-medium text-gray-700 mb-2">
                                        Year
                                    </label>
                                    <select 
                                        id="year"
                                        v-model="form.year"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        required
                                    >
                                        <option v-for="year in availableYears" :key="year" :value="year">
                                            {{ year }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Month Selection -->
                                <div>
                                    <label for="month" class="block text-sm font-medium text-gray-700 mb-2">
                                        Month
                                    </label>
                                    <select 
                                        id="month"
                                        v-model="form.month"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        required
                                    >
                                        <option v-for="(month, index) in months" :key="index + 1" :value="index + 1">
                                            {{ month }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Generate Button -->
                            <div class="mt-6 flex justify-between items-center">
                                <div class="flex items-center space-x-4">
                                    <button
                                        type="submit"
                                        :disabled="isGenerating"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    >
                                        <svg v-if="isGenerating" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ isGenerating ? 'Generating...' : 'Generate Report' }}
                                    </button>
                                    
                                    <label class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            v-model="form.force"
                                            class="mr-2 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        >
                                        <span class="text-sm text-gray-700">Force regenerate if exists</span>
                                    </label>
                                </div>
                                
                                <button
                                    type="button"
                                    @click="checkReportStatus"
                                    :disabled="!form.year || !form.month"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                >
                                    Check Report Status
                                </button>
                            </div>
                        </form>

                        <!-- Status Messages -->
                        <div v-if="statusMessage" class="mb-6 p-4 rounded-md" :class="statusMessage.type === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'">
                            {{ statusMessage.message }}
                        </div>

                        <!-- Recent Reports -->
                        <div class="mt-12">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Reports</h3>
                            <div class="bg-gray-50 rounded-lg p-6">
                                <p class="text-gray-500 text-center">No recent reports available</p>
                                <p class="text-sm text-gray-400 text-center mt-2">Generated reports will appear here</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useToast } from 'vue-toastification';
import axios from 'axios'
import Swal from 'sweetalert2'

const toast = useToast();

// Props
const props = defineProps({
    facility: Object,
    currentYear: Number,
    currentMonth: Number,
})

// Reactive data
const form = ref({
    year: props.currentYear,
    month: props.currentMonth,
    force: false
})
const isGenerating = ref(false)
const statusMessage = ref(null)
const isCheckingStatus = ref(false)
const reportStatus = ref(null)

// Computed properties
const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    return Array.from({ length: 6 }, (_, i) => currentYear - i)
})

const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
]

// Methods
const generateReport = async () => {
    isGenerating.value = true
    statusMessage.value = null
    
    try {
        const response = await axios.post('/reports/monthly-inventory/generate', {
            year: form.value.year,
            month: form.value.month,
            force: form.value.force,
        })
        
        toast.success(response.data.message || 'Report generation started successfully!')
        
        statusMessage.value = {
            type: 'success',
            message: response.data.message
        }
    } catch (error) {
        console.error('Report generation error:', error)
        
        const errorMessage = error.response?.data?.message || 
                            error.response?.data?.errors || 
                            'An unexpected error occurred. Please try again later.'
        
        toast.error(errorMessage)
        
        statusMessage.value = {
            type: 'error',
            message: typeof errorMessage === 'string' ? errorMessage : 'Failed to generate report'
        }
    } finally {
        isGenerating.value = false
    }
}

const checkReportStatus = async () => {
    if (!form.value.year || !form.value.month) {
        toast.error('Please select both year and month to check status')
        return
    }

    isCheckingStatus.value = true
    reportStatus.value = null
    
    try {
        const reportPeriod = `${form.value.year}-${String(form.value.month).padStart(2, '0')}`
        const response = await axios.get('/reports/monthly-inventory/status', {
            params: { report_period: reportPeriod }
        })

        if (response.data.success && response.data.exists) {
            reportStatus.value = response.data
            
            // Show success status with SweetAlert
            await Swal.fire({
                title: '📊 Report Found!',
                html: `
                    <div class="text-left space-y-3">
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <p class="font-semibold text-blue-900">Report Details:</p>
                            <p class="text-blue-800">Period: ${reportPeriod}</p>
                            <p class="text-blue-800">Status: <span class="font-medium">${response.data.report?.status || 'Unknown'}</span></p>
                            <p class="text-blue-800">Total Items: ${response.data.summary?.total_items || 0}</p>
                        </div>
                        
                        ${response.data.audit_trail?.length > 0 ? `
                        <div class="bg-green-50 p-3 rounded-lg">
                            <p class="font-semibold text-green-900 mb-2">Recent Actions:</p>
                            ${response.data.audit_trail.slice(-2).map(action => `
                                <p class="text-sm text-green-800">
                                    <span class="font-medium">${action.status}</span> by ${action.user}
                                    <br><span class="text-xs">${new Date(action.timestamp).toLocaleString()}</span>
                                </p>
                            `).join('')}
                        </div>
                        ` : ''}
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'View Report',
                showCancelButton: true,
                cancelButtonText: 'Close',
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                customClass: {
                    popup: 'text-sm'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    viewReport()
                }
            })
        } else {
            // Show "not found" status with custom SweetAlert
            const monthName = months[form.value.month - 1]
            
            await Swal.fire({
                title: '📋 No Report Found',
                html: `
                    <div class="text-left space-y-4">
                        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                            <p class="text-yellow-800 mb-2">
                                <span class="font-semibold">Report Period:</span> ${monthName} ${form.value.year}
                            </p>
                            <p class="text-yellow-700 text-sm">
                                No monthly inventory report has been generated for this period yet.
                            </p>
                        </div>
                        
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <p class="text-blue-800 font-semibold mb-2">📝 What would you like to do?</p>
                            <ul class="text-blue-700 text-sm space-y-1">
                                <li>• Generate a new report for this period</li>
                                <li>• Select a different month/year</li>
                                <li>• Check if inventory data exists for this period</li>
                            </ul>
                        </div>
                        
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-gray-600 text-xs">
                                <strong>Facility:</strong> ${props.facility?.name || 'Current Facility'}
                            </p>
                        </div>
                    </div>
                `,
                icon: 'info',
                confirmButtonText: '🔄 Generate Report Now',
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280',
                customClass: {
                    popup: 'text-sm max-w-lg'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    generateReport()
                }
            })
        }
    } catch (error) {
        console.error('Error checking report status:', error)
        
        // Show error with SweetAlert
        await Swal.fire({
            title: '❌ Error Checking Status',
            html: `
                <div class="text-left space-y-3">
                    <div class="bg-red-50 p-3 rounded-lg border border-red-200">
                        <p class="text-red-800 font-semibold mb-2">Unable to check report status</p>
                        <p class="text-red-700 text-sm">
                            ${error.response?.data?.message || 'An unexpected error occurred while checking the report status.'}
                        </p>
                    </div>
                    
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <p class="text-gray-600 text-xs">
                            <strong>Error Code:</strong> ${error.response?.status || 'Unknown'}
                        </p>
                    </div>
                </div>
            `,
            icon: 'error',
            confirmButtonText: 'Try Again',
            showCancelButton: true,
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280'
        }).then((result) => {
            if (result.isConfirmed) {
                checkReportStatus()
            }
        })
    } finally {
        isCheckingStatus.value = false
    }
}

const formatDateTime = (dateString) => {
    if (!dateString) return 'Unknown'
    return new Date(dateString).toLocaleString()
}

const formatNumber = (value) => {
    if (value === null || value === undefined || value === 0) return '0'
    return Number(value).toLocaleString()
}

const viewReport = () => {
    const reportPeriod = `${form.value.year}-${String(form.value.month).padStart(2, '0')}`
    router.get('/reports/monthly-inventory/view', {
        report_period: reportPeriod,
    })
}
</script>
