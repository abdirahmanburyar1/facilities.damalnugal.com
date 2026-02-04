<template>
    <AuthenticatedLayout 
        title="LMIS Monthly Report"
        description="Detailed view of LMIS monthly inventory report with approval workflow and data export options"
        img="/assets/images/report.png"
    >
        <div class="mb-6">
            <h2 class="text-xs font-semibold leading-tight text-gray-800">
                LMIS Monthly Report: {{ monthName }}
            </h2>
        </div>

        <div class="p-2 mb-[80px]">
            <div class="mx-auto max-w-full">
                <!-- Facility Information Card -->
                <div class="mb-6 bg-white shadow-sm rounded-lg">
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-xs font-medium text-gray-900 mb-3">Facility Information</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <dt class="text-xs font-medium text-gray-500">Facility Name</dt>
                                <dd class="text-xs text-gray-900">{{ facility?.name || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">Facility Code</dt>
                                <dd class="text-xs text-gray-900">{{ facility?.code || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">Facility Type</dt>
                                <dd class="text-xs text-gray-900">{{ facility?.facility_type || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">District</dt>
                                <dd class="text-xs text-gray-900">{{ facility?.district || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">Address</dt>
                                <dd class="text-xs text-gray-900">{{ facility?.address || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">Report Period</dt>
                                <dd class="text-xs text-gray-900">{{ monthName }}</dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Status and Period -->
                <div class="mb-6 bg-white shadow-sm rounded-lg">
                    <div class="p-2 border-b border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Report Status</label>
                                <span :class="getStatusClass(props.reports.status)" class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                    {{ getStatusText(props.reports.status) }}
                                </span>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Report Period</label>
                                <span class="mt-1 text-xs text-gray-900">{{ monthName }}</span>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Last Updated</label>
                                <span class="mt-1 text-xs text-gray-900">{{ formatDateTime(props.reports.updated_at) }}</span>
                            </div>
                        </div>

                        <!-- Workflow Audit Trail -->
                        <div class="mt-4 border-t pt-4">
                            <h4 class="text-xs font-medium text-gray-700 mb-3">Workflow History</h4>
                            <div class="space-y-2">
                                <!-- Workflow History (LIFO - Most Recent First) -->
                                <div 
                                    v-for="(event, index) in workflowHistory" 
                                    :key="index"
                                    class="flex items-center text-xs"
                                    :class="event.textColor"
                                >
                                    <svg class="w-4 h-4 mr-2" :class="event.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="event.iconPath"></path>
                                    </svg>
                                    <div class="flex-1">
                                        <span class="font-medium">{{ event.label }}:</span>
                                        <span class="ml-1">{{ formatDateTime(event.timestamp) }}</span>
                                        <span v-if="event.user" class="ml-1" :class="event.userColor">
                                            by {{ event.user }}
                                        </span>
                                        <div v-if="event.reason" class="ml-6 mt-1 text-xs text-red-600 italic">
                                            Reason: {{ event.reason }}
                                        </div>
                                    </div>
                                    <span class="ml-2 px-2 py-1 text-xs rounded-full" :class="event.badgeClass">
                                        {{ event.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div v-if="props.reports" class="mb-6 bg-white shadow-sm rounded-lg">
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-sm font-medium text-gray-900 mb-4">Actions</h3>
                        <div class="mt-8 flex justify-center space-x-4">
                            <!-- Submit Report (draft -> submitted) -->
                            <button
                                v-if="props.reports.status === 'draft'"
                                @click="submitForReview"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition duration-200"
                            >
                                📝 Submit Report
                            </button>

                            <!-- Review Report (submitted -> reviewed) -->
                            <button
                                v-if="props.reports.status === 'submitted'"
                                @click="startReview"
                                class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-6 rounded-lg transition duration-200"
                            >
                                👁️ Mark as Reviewed
                            </button>

                            <!-- Approve Report (reviewed -> approved) -->
                            <button
                                v-if="props.reports.status === 'reviewed' && props.canApprove"
                                @click="approveReport"
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition duration-200"
                            >
                                ✅ Approve Report
                            </button>

                            <!-- Reject Report (submitted/reviewed -> rejected) -->
                            <button
                                v-if="['submitted', 'reviewed'].includes(props.reports.status) && props.canApprove"
                                @click="rejectReport"
                                class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg transition duration-200"
                            >
                                ❌ Reject Report
                            </button>

                            <!-- Return to Draft (any status -> draft) -->
                            <button
                                v-if="props.reports.status !== 'draft'"
                                @click="returnToDraft"
                                class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded-lg transition duration-200"
                            >
                                📋 Return to Draft
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Report Items Table -->
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="border-b border-gray-200">
                        <div class="mt-2 text-xs text-gray-500">
                            <strong>Formula:</strong> Closing Balance = Beginning Balance + Qty Received - Qty Consumed + Positive Adjustments - Negative Adjustments
                        </div>
                    </div>

                    <div v-if="props.reports && props.reports.items" class="overflow-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="p-1 text-left text-xs text-black capitalize border-r border-gray-200">
                                        Item / Dose Strength /Dosage Form
                                    </th>
                                    <th class="p-1 text-left text-xs text-black capitalize border-r border-gray-200">
                                        Beginning Balance
                                    </th>
                                    <th class="p-1 text-left text-xs text-black capitalize border-r border-gray-200">
                                        Qty Received
                                    </th>
                                    <th class="p-1 text-left text-xs text-black capitalize border-r border-gray-200">
                                        Qty Consumed
                                    </th>
                                    <th class="p-1 text-left text-xs text-black capitalize border-r border-gray-200 bg-yellow-50">
                                        Positive Adjustment
                                    </th>
                                    <th class="p-1 text-left text-xs text-black capitalize border-r border-gray-200 bg-yellow-50">
                                        Negative Adjustment
                                    </th>
                                    <th class="p-1 text-left text-xs text-black capitalize border-r border-gray-200 bg-blue-50">
                                        Closing Balance
                                    </th>
                                    <th class="p-1 text-left text-xs text-black capitalize bg-yellow-50">
                                        Stockout Days
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <tr 
                                    v-for="item in props.reports.items" 
                                    :key="item.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-3 py-2 text-xs text-gray-900 border-r border-gray-200">
                                        {{ item.product?.name || item.product_name }}
                                    </td>
                                    <td class="px-3 py-2 text-xs text-right text-gray-900 border-r border-gray-200">
                                        {{ formatNumber(item.opening_balance) }}
                                    </td>
                                    <td class="px-3 py-2 text-xs text-right text-green-600 border-r border-gray-200">
                                        {{ formatNumber(item.stock_received) }}
                                    </td>
                                    <td class="px-3 py-2 text-xs text-right text-red-600 border-r border-gray-200">
                                        {{ formatNumber(item.stock_issued) }}
                                    </td>
                                    <td class="px-3 py-2 text-xs text-right border-r border-gray-200 bg-yellow-50">
                                        <input
                                            v-if="!isApproved"
                                            v-model.number="item.positive_adjustments"
                                            @input="updateClosingBalance(item)"
                                            @blur="saveItemChanges(item)"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full px-2 py-1 text-right border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        />
                                        <span v-else class="text-green-600">
                                            {{ formatNumber(item.positive_adjustments) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-xs text-right border-r border-gray-200 bg-yellow-50">
                                        <input
                                            v-if="!isApproved"
                                            v-model.number="item.negative_adjustments"
                                            @input="updateClosingBalance(item)"
                                            @blur="saveItemChanges(item)"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full px-2 py-1 text-right border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        />
                                        <span v-else class="text-red-600">
                                            {{ formatNumber(item.negative_adjustments) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-xs text-right font-semibold border-r border-gray-200 bg-blue-50" 
                                        :class="getBalanceClass(item.closing_balance)">
                                        {{ formatNumber(item.closing_balance) }}
                                    </td>
                                    <td class="px-3 py-2 text-xs text-right bg-yellow-50">
                                        <input
                                            v-if="!isApproved"
                                            v-model.number="item.stockout_days"
                                            @blur="saveItemChanges(item)"
                                            type="number"
                                            min="0"
                                            max="31"
                                            class="w-full px-2 py-1 text-right border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        />
                                        <span v-else class="text-orange-600">
                                            {{ item.stockout_days || 0 }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- No reports message -->
                    <div v-else-if="noReportsFound" class="p-12 text-center">
                        <div class="text-gray-400 mb-4">
                            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xs font-medium text-gray-900 mb-2">{{ message }}</h3>
                        <p class="text-xs text-gray-500">No monthly inventory report found for the selected period.</p>
                        <Link 
                            to="/reports/monthly-inventory"
                            class="mt-4 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Generate New Report
                        </Link>
                    </div>
                    <div v-else class="p-12 text-center">
                        <div class="text-gray-400 mb-4">
                            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xs font-medium text-gray-900 mb-2">No Report Data</h3>
                        <p class="text-xs text-gray-500">No monthly inventory report found for the selected period.</p>
                        <Link 
                            to="/reports/monthly-inventory"
                            class="mt-4 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Generate New Report
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'
import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import 'jspdf-autotable'
import Swal from 'sweetalert2'
import { useToast } from 'vue-toastification'
import { router } from '@inertiajs/vue3'
import { computed } from 'vue'

const toast = useToast()

const props = defineProps({
    reports: Object,
    facility: Object,
    reportPeriod: String,
    monthName: String,
    isApproved: Boolean,
    noReportsFound: Boolean,
    message: String,
    canApprove: Boolean
})

// Helper function to generate route URLs
const route = (name, params = {}) => {
    const routes = {
        'reports.monthly-inventory.submit': '/reports/monthly-inventory/submit',
        'reports.monthly-inventory.start-review': '/reports/monthly-inventory/start-review',
        'reports.monthly-inventory.approve': '/reports/monthly-inventory/approve',
        'reports.monthly-inventory.reject': '/reports/monthly-inventory/reject',
        'reports.monthly-inventory.return-to-draft': '/reports/monthly-inventory/return-to-draft',
        'reports.monthly-inventory.update-item': '/reports/monthly-inventory/update-item',
        'reports.monthly-inventory.save': '/reports/monthly-inventory/save',
        'reports.monthly-inventory.reopen': '/reports/monthly-inventory/reopen'
    }
    return routes[name] || name
}

// Function to refresh report data
const fetchReportData = async () => {
    router.reload({
        only: ['reports', 'facility', 'reportPeriod', 'monthName', 'isApproved', 'noReportsFound', 'message', 'canApprove']
    })
}

const formatNumber = (value) => {
    if (value === null || value === undefined || value === 0 || value === '0') return '-'
    return Number(value).toLocaleString()
}

const getBalanceClass = (balance) => {
    const bal = Number(balance) || 0
    if (bal === 0) return 'text-red-600 font-medium'
    if (bal < 10) return 'text-orange-600 font-medium'
    return 'text-gray-900'
}

const getStatusClass = (status) => {
    if (status === 'approved') return 'bg-green-100 text-green-800'
    if (status === 'draft') return 'bg-yellow-100 text-yellow-800'
    if (status === 'submitted') return 'bg-blue-100 text-blue-800'
    if (status === 'reviewed') return 'bg-indigo-100 text-indigo-800'
    if (status === 'rejected') return 'bg-red-100 text-red-800'
    return 'bg-gray-100 text-gray-800'
}

const getStatusText = (status) => {
    if (status === 'approved') return 'Approved'
    if (status === 'draft') return 'Draft'
    if (status === 'submitted') return 'Submitted for Review'
    if (status === 'reviewed') return 'Under Review'
    if (status === 'rejected') return 'Rejected'
    return 'Unknown Status'
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString()
}

const formatDateTime = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleString()
}

const updateClosingBalance = (item) => {
    item.closing_balance = item.opening_balance + item.stock_received - item.stock_issued + item.positive_adjustments - item.negative_adjustments
}

const saveItemChanges = async (item) => {
    try {
        const response = await axios.post(route('reports.monthly-inventory.update-item'), {
            item_id: item.id,
            positive_adjustments: item.positive_adjustments || 0,
            negative_adjustments: item.negative_adjustments || 0,
            stockout_days: item.stockout_days || 0,
        })
        
        if (response.data.success) {
            // Show a subtle success indicator
            console.log('Item updated successfully')
        }
    } catch (error) {
        console.error('Error updating item:', error)
        Swal.fire({
            title: 'Error Updating Item',
            text: 'An error occurred while updating the item.',
            icon: 'error',
            timer: 3000,
            showConfirmButton: false
        })
    }
}

const saveChanges = async () => {
    Swal.fire({
        title: 'Save Changes?',
        text: 'Are you sure you want to save all changes to this report?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, save changes'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(route('reports.monthly-inventory.save'), {
                report_period: props.reportPeriod,
                reports: props.reports
            })
            .then((response) => {
                if (response.data.success) {
                    Swal.fire({
                        title: 'Changes Saved!',
                        text: 'All changes have been successfully saved.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    })
                } else {
                    Swal.fire({
                        title: 'Error Saving Changes',
                        text: response.data.message || 'An error occurred while saving changes.',
                        icon: 'error',
                    })
                }
            })
            .catch((error) => {
                console.error('Error saving changes:', error)
                Swal.fire({
                    title: 'Error Saving Changes',
                    text: 'An error occurred while saving changes.',
                    icon: 'error',
                })
            })
        }
    })
}

const submitForReview = async () => {
    if (!props.reports) return

    const result = await Swal.fire({
        title: 'Submit for Review?',
        text: 'Are you sure you want to submit this report for review? You will not be able to edit it after submission.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, submit for review!'
    })

    if (result.isConfirmed) {
        try {
            const response = await axios.post(route('reports.monthly-inventory.submit'), {
                report_id: props.reports.id
            })

            if (response.data.success) {
                toast.success('📋 Report submitted for review successfully!')
                await fetchReportData()
            } else {
                toast.error(response.data.message || 'Failed to submit report')
            }
        } catch (error) {
            console.error('Error submitting report:', error)
            toast.error('An error occurred while submitting the report')
        }
    }
}

const startReview = async () => {
    if (!props.reports) return

    const result = await Swal.fire({
        title: 'Start Review?',
        text: 'Are you sure you want to start reviewing this report? This will mark the review process as begun.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#8b5cf6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, start review!'
    })

    if (result.isConfirmed) {
        try {
            const response = await axios.post(route('reports.monthly-inventory.start-review'), {
                report_id: props.reports.id
            })

            if (response.data.success) {
                toast.success('👁️ Report marked as reviewed successfully!')
                await fetchReportData()
            } else {
                toast.error(response.data.message || 'Failed to review report')
            }
        } catch (error) {
            console.error('Error reviewing report:', error)
            toast.error('An error occurred while reviewing the report')
        }
    }
}

const approveReport = async () => {
    if (!props.reports) return

    const result = await Swal.fire({
        title: 'Approve Report?',
        text: 'Are you sure you want to approve this monthly inventory report?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, approve it!'
    })

    if (result.isConfirmed) {
        try {
            const response = await axios.post(route('reports.monthly-inventory.approve'), {
                report_id: props.reports.id
            })

            if (response.data.success) {
                toast.success('✅ Report approved successfully!')
                await fetchReportData()
            } else {
                toast.error(response.data.message || 'Failed to approve report')
            }
        } catch (error) {
            console.error('Error approving report:', error)
            toast.error('An error occurred while approving the report')
        }
    }
}

const rejectReport = async () => {
    if (!props.reports) return

    const { value: comments } = await Swal.fire({
        title: 'Reject Report',
        text: 'Please provide a reason for rejecting this report:',
        input: 'textarea',
        inputPlaceholder: 'Enter rejection reason...',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Reject Report',
        inputValidator: (value) => {
            if (!value) {
                return 'You need to provide a reason for rejection!'
            }
        }
    })

    if (comments) {
        try {
            const response = await axios.post(route('reports.monthly-inventory.reject'), {
                report_id: props.reports.id,
                comments: comments
            })

            if (response.data.success) {
                toast.success('❌ Report rejected successfully!')
                await fetchReportData()
            } else {
                toast.error(response.data.message || 'Failed to reject report')
            }
        } catch (error) {
            console.error('Error rejecting report:', error)
            toast.error('An error occurred while rejecting the report')
        }
    }
}

const returnToDraft = async () => {
    if (!props.reports) return

    const result = await Swal.fire({
        title: 'Return to Draft?',
        text: 'This will return the report to draft status for further editing.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#6b7280',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, return to draft'
    })

    if (result.isConfirmed) {
        try {
            const response = await axios.post(route('reports.monthly-inventory.return-to-draft'), {
                report_id: props.reports.id
            })

            if (response.data.success) {
                toast.success('📋 Report returned to draft successfully!')
                await fetchReportData()
            } else {
                toast.error(response.data.message || 'Failed to return report to draft')
            }
        } catch (error) {
            console.error('Error returning report to draft:', error)
            toast.error('An error occurred while returning the report to draft')
        }
    }
}

const goBack = () => {
    window.history.back()
}

const exportToPDF = () => {
    const { jsPDF } = window.jspdf
    const doc = new jsPDF('landscape', 'mm', 'a4')
    
    // Set up document properties
    doc.setProperties({
        title: `LMIS Monthly Report - ${props.facility?.name || 'Facility'}`,
        subject: `Monthly Inventory Report for ${props.monthName} ${props.reportPeriod}`,
        author: 'LMIS System',
        creator: 'LMIS Monthly Report Generator'
    })

    let yPos = 20
    const pageWidth = doc.internal.pageSize.getWidth()
    const margin = 15

    // Header Section
    doc.setFontSize(18)
    doc.setFont('helvetica', 'bold')
    doc.text('Monthly Inventory Report', pageWidth / 2, yPos, { align: 'center' })
    yPos += 15

    // Facility and Report Information
    doc.setFontSize(12)
    doc.setFont('helvetica', 'normal')
    doc.text(`Facility: ${props.facility?.name || 'Unknown Facility'}`, margin, yPos)
    yPos += 8
    doc.text(`Report Period: ${props.monthName} ${props.reportPeriod}`, margin, yPos)
    yPos += 8
    doc.text(`Status: ${props.reports.status?.toUpperCase() || 'UNKNOWN'}`, margin, yPos)
    yPos += 8
    doc.text(`Generated: ${new Date().toLocaleString()}`, margin, yPos)
    yPos += 15

    // Workflow History Section
    doc.setFontSize(14)
    doc.setFont('helvetica', 'bold')
    doc.text('Workflow History (Most Recent First)', margin, yPos)
    yPos += 10

    doc.setFontSize(10)
    doc.setFont('helvetica', 'normal')
    workflowHistory.value.forEach(event => {
        if (yPos > 180) { // Check if we need a new page
            doc.addPage()
            yPos = 20
        }
        
        const eventText = `${event.label}: ${formatDateTime(event.timestamp)}`
        const userText = event.user ? ` by ${event.user}` : ''
        doc.text(`• ${eventText}${userText}`, margin + 5, yPos)
        yPos += 6
        
        if (event.reason) {
            doc.setFont('helvetica', 'italic')
            doc.text(`  Reason: ${event.reason}`, margin + 10, yPos)
            doc.setFont('helvetica', 'normal')
            yPos += 6
        }
    })
    yPos += 10

    // Summary Statistics
    const totalItems = props.reports.items?.length || 0
    const totalReceived = props.reports.items?.reduce((sum, item) => sum + (item.stock_received || 0), 0) || 0
    const totalIssued = props.reports.items?.reduce((sum, item) => sum + (item.stock_issued || 0), 0) || 0
    const totalClosing = props.reports.items?.reduce((sum, item) => sum + calculateClosingBalance(item), 0) || 0

    doc.setFontSize(14)
    doc.setFont('helvetica', 'bold')
    doc.text('Summary Statistics', margin, yPos)
    yPos += 8

    doc.setFontSize(10)
    doc.setFont('helvetica', 'normal')
    doc.text(`Total Items: ${totalItems.toLocaleString()}`, margin + 5, yPos)
    yPos += 6
    doc.text(`Total Stock Received: ${totalReceived.toLocaleString()}`, margin + 5, yPos)
    yPos += 6
    doc.text(`Total Stock Issued: ${totalIssued.toLocaleString()}`, margin + 5, yPos)
    yPos += 6
    doc.text(`Total Closing Balance: ${totalClosing.toLocaleString()}`, margin + 5, yPos)
    yPos += 15

    // Check if we need a new page for the table
    if (yPos > 150) {
        doc.addPage()
        yPos = 20
    }

    // Data Table
    if (props.reports.items && props.reports.items.length > 0) {
        const tableData = props.reports.items.map((item, index) => {
            const closingBalance = calculateClosingBalance(item)
            const status = closingBalance <= 0 ? 'STOCKOUT' : closingBalance < 10 ? 'LOW STOCK' : 'IN STOCK'
            
            return [
                item.product?.name || item.product_name || `Item ${index + 1}`,
                (item.opening_balance || 0).toLocaleString(),
                (item.stock_received || 0).toLocaleString(),
                (item.stock_issued || 0).toLocaleString(),
                (item.positive_adjustments || 0).toLocaleString(),
                (item.negative_adjustments || 0).toLocaleString(),
                closingBalance.toLocaleString(),
                (item.stockout_days || 0).toString(),
                status
            ]
        })

        doc.autoTable({
            head: [[
                'Item / Dose Strength / Dosage Form',
                'Opening Balance',
                'Stock Received',
                'Stock Issued',
                'Positive Adj.',
                'Negative Adj.',
                'Closing Balance',
                'Stockout Days',
                'Status'
            ]],
            body: tableData,
            startY: yPos,
            margin: { left: margin, right: margin },
            styles: {
                fontSize: 8,
                cellPadding: 3,
                overflow: 'linebreak',
                halign: 'center'
            },
            headStyles: {
                fillColor: [52, 73, 94],
                textColor: 255,
                fontStyle: 'bold',
                fontSize: 9
            },
            columnStyles: {
                0: { halign: 'left', cellWidth: 50 }, // Item name
                1: { halign: 'right', cellWidth: 20 }, // Opening Balance
                2: { halign: 'right', cellWidth: 20 }, // Stock Received
                3: { halign: 'right', cellWidth: 20 }, // Stock Issued
                4: { halign: 'right', cellWidth: 20 }, // Positive Adj.
                5: { halign: 'right', cellWidth: 20 }, // Negative Adj.
                6: { halign: 'right', cellWidth: 20 }, // Closing Balance
                7: { halign: 'center', cellWidth: 18 }, // Stockout Days
                8: { halign: 'center', cellWidth: 18 } // Status
            },
            alternateRowStyles: {
                fillColor: [245, 245, 245]
            },
            didDrawCell: function(data) {
                // Highlight stockout and low stock rows
                if (data.column.index === 8 && data.cell.raw === 'STOCKOUT') {
                    doc.setFillColor(252, 220, 220)
                } else if (data.column.index === 8 && data.cell.raw === 'LOW STOCK') {
                    doc.setFillColor(255, 248, 220)
                }
            }
        })
    } else {
        doc.setFontSize(12)
        doc.setFont('helvetica', 'italic')
        doc.text('No inventory data available for this report period.', pageWidth / 2, yPos, { align: 'center' })
    }

    // Footer with generation info
    const pageCount = doc.internal.getNumberOfPages()
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i)
        const footerY = doc.internal.pageSize.getHeight() - 10
        doc.setFontSize(8)
        doc.setFont('helvetica', 'normal')
        doc.text(`Generated on ${new Date().toLocaleString()} | Page ${i} of ${pageCount}`, 
                 pageWidth / 2, footerY, { align: 'center' })
    }

    // Generate enhanced filename with timestamp
    const timestamp = new Date().toISOString().slice(0, 16).replace(/[:-]/g, '')
    const facilityName = (props.facility?.name || 'Facility').replace(/[^a-zA-Z0-9]/g, '_')
    const filename = `LMIS_Monthly_Report_${facilityName}_${props.reportPeriod}_${timestamp}.pdf`
    
    // Save the PDF
    doc.save(filename)
    
    // Show success message
    toast.success('PDF report exported successfully!')
}

const exportExcel = () => {
    if (!props.reports || !props.reports.items) {
        alert('No data to export')
        return
    }

    // Prepare data for Excel export
    const excelData = []
    
    // Add facility info header
    excelData.push([
        `LMIS Monthly Report - ${props.facility?.name || 'Unknown Facility'}`,
        '',
        '',
        '',
        '',
        '',
        '',
        ''
    ])
    excelData.push([
        `Report Period: ${props.monthName}`,
        '',
        '',
        '',
        '',
        '',
        '',
        ''
    ])
    excelData.push(['', '', '', '', '', '', '', '']) // Empty row

    // Add headers
    excelData.push([
        'Item / Dose Strength / Dosage Form',
        'Beginning Balance',
        'Qty Received',
        'Qty Consumed',
        'Positive Adjustment',
        'Negative Adjustment',
        'Closing Balance',
        'Stockout Days'
    ])

    // Add data rows
    props.reports.items.forEach(item => {
        excelData.push([
            item.product?.name || item.product_name || '',
            item.opening_balance || 0,
            item.stock_received || 0,
            item.stock_issued || 0,
            item.positive_adjustments || 0,
            item.negative_adjustments || 0,
            calculateClosingBalance(item),
            item.stockout_days || 0
        ])
    })

    // Create worksheet
    const ws = XLSX.utils.aoa_to_sheet(excelData)
    
    // Set column widths
    ws['!cols'] = [
        { width: 30 }, // Item name
        { width: 15 }, // Beginning Balance
        { width: 12 }, // Qty Received
        { width: 12 }, // Qty Consumed
        { width: 15 }, // Positive Adjustment
        { width: 15 }, // Negative Adjustment
        { width: 15 }, // Closing Balance
        { width: 12 }  // Stockout Days
    ]

    // Create workbook and add worksheet
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'Monthly Report')

    // Generate filename
    const filename = `LMIS_Monthly_Report_${props.facility?.name || 'Facility'}_${props.reportPeriod}.xlsx`
    
    // Save file
    XLSX.writeFile(wb, filename)
}

const calculateClosingBalance = (item) => {
    return item.opening_balance + item.stock_received - item.stock_issued + item.positive_adjustments - item.negative_adjustments
}

const reopenReport = async () => {
    Swal.fire({
        title: 'Reopen Report?',
        text: 'Are you sure you want to reopen this report?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, reopen report'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(route('reports.monthly-inventory.reopen'), {
                report_id: props.reports.id,
            })
            .then((response) => {
                if (response.data.success) {
                    Swal.fire({
                        title: 'Report Reopened!',
                        text: 'The report has been successfully reopened.',
                        icon: 'success',
                    })
                } else {
                    Swal.fire({
                        title: 'Error Reopening Report',
                        text: 'An error occurred while reopening the report.',
                        icon: 'error',
                    })
                }
            })
            .catch((error) => {
                console.error('Error reopening report:', error)
                Swal.fire({
                    title: 'Error Reopening Report',
                    text: 'An error occurred while reopening the report.',
                    icon: 'error',
                })
            })
        }
    })
}

const workflowHistory = computed(() => {
    const events = []

    // Always add Created first (oldest event)
    events.push({
        label: 'Created',
        timestamp: props.reports.created_at,
        iconPath: 'M12 6v6m0 0v6m0-6h6m-6 0H6',
        iconColor: 'text-gray-500',
        textColor: 'text-gray-700',
        badgeClass: 'bg-gray-200 text-gray-800',
        status: 'Created'
    })

    if (props.reports.submitted_at) {
        events.push({
            label: 'Submitted',
            timestamp: props.reports.submitted_at,
            user: props.reports.submitted_by?.name || 'Unknown User',
            iconPath: 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8',
            iconColor: 'text-blue-600',
            textColor: 'text-blue-700',
            badgeClass: 'bg-blue-200 text-blue-900',
            status: 'Submitted',
            userColor: 'text-blue-700'
        })
    }

    if (props.reports.reviewed_at) {
        events.push({
            label: 'Reviewed',
            timestamp: props.reports.reviewed_at,
            user: props.reports.reviewed_by?.name || 'Unknown User',
            iconPath: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
            iconColor: 'text-indigo-600',
            textColor: 'text-indigo-700',
            badgeClass: 'bg-indigo-200 text-indigo-900',
            status: 'Reviewed',
            userColor: 'text-indigo-700'
        })
    }

    if (props.reports.approved_at) {
        events.push({
            label: 'Approved',
            timestamp: props.reports.approved_at,
            user: props.reports.approved_by?.name || 'Unknown User',
            iconPath: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            iconColor: 'text-green-600',
            textColor: 'text-green-700',
            badgeClass: 'bg-green-200 text-green-900',
            status: 'Approved',
            userColor: 'text-green-700'
        })
    }

    if (props.reports.rejected_at) {
        events.push({
            label: 'Rejected',
            timestamp: props.reports.rejected_at,
            user: props.reports.rejected_by?.name || 'Unknown User',
            reason: props.reports.rejection_reason,
            iconPath: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
            iconColor: 'text-red-600',
            textColor: 'text-red-700',
            badgeClass: 'bg-red-200 text-red-900',
            status: 'Rejected',
            userColor: 'text-red-700'
        })
    }

    if (props.reports.reopened_at) {
        events.push({
            label: 'Reopened',
            timestamp: props.reports.reopened_at,
            user: props.reports.reopened_by?.name || 'Unknown User',
            iconPath: 'M11 5H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
            iconColor: 'text-yellow-600',
            textColor: 'text-yellow-700',
            badgeClass: 'bg-yellow-200 text-yellow-900',
            status: 'Reopened',
            userColor: 'text-yellow-700'
        })
    }

    // Return in chronological order (oldest first - FIFO)
    return events
})
</script>

<style scoped>
/* Ensure proper table borders */
table {
    border-collapse: separate;
    border-spacing: 0;
}

th:first-child,
td:first-child {
    border-left: 1px solid #e5e7eb;
}

th:last-child,
td:last-child {
    border-right: 1px solid #e5e7eb;
}

thead th {
    border-top: 1px solid #e5e7eb;
    border-bottom: 2px solid #d1d5db;
}

tbody tr:last-child td {
    border-bottom: 1px solid #e5e7eb;
}

@media print {
    nav, button, .shadow-sm { display: none !important; }
    @page { margin: 0.75in; size: A4 landscape; }
    body { font-family: Arial !important; color: black !important; }
    table { font-size: 8px !important; }
    th, td { border: 1px solid black !important; padding: 3px !important; }
}
</style>
