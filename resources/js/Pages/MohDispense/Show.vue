<template>
    <AuthenticatedLayout title="MOH Dispense Details" description="View MOH dispense record details">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ mohDispense.moh_dispense_number }}</h1>
                    <p class="mt-1 text-sm text-gray-600">MOH Dispense Record Details</p>
                </div>
                <div class="flex space-x-3">
                    <button v-if="mohDispense.status === 'draft'" 
                        @click="validateInventory"
                        :disabled="validating"
                        class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 disabled:opacity-50">
                        {{ validating ? 'Validating...' : 'Validate Inventory' }}
                    </button>
                    <button v-if="mohDispense.status === 'draft'" 
                        @click="processDispense"
                        :disabled="processing"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                        {{ processing ? 'Processing...' : 'Process Dispense' }}
                    </button>
                    <Link :href="route('moh-dispense.index')"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
                        Back to List
                    </Link>
                </div>
            </div>

            <!-- Status and Info -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Basic Info -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">MOH Number</dt>
                            <dd class="text-sm text-gray-900">{{ mohDispense.moh_dispense_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd>
                                <span :class="getStatusClass(mohDispense.status)"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    {{ mohDispense.status }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created By</dt>
                            <dd class="text-sm text-gray-900">{{ mohDispense.created_by?.name || 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created At</dt>
                            <dd class="text-sm text-gray-900">{{ formatDate(mohDispense.created_at) }}</dd>
                        </div>
                    </dl>
                </div>


                <!-- Summary -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Summary</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Total Items</dt>
                            <dd class="text-sm text-gray-900">{{ mohDispense.items?.length || 0 }} items</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Total Quantity</dt>
                            <dd class="text-sm text-gray-900">{{ totalQuantity }} units</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Items Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Dispensed Items</h3>
                </div>
                
                <div v-if="mohDispense.items && mohDispense.items.length > 0">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiry Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dispense Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dispensed By</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="item in mohDispense.items" :key="item.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ item.product?.name || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ item.source || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ item.batch_no || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDate(item.expiry_date) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ item.quantity }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDate(item.dispense_date) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ item.dispensed_by || 'N/A' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div v-else class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No items</h3>
                    <p class="mt-1 text-sm text-gray-500">This MOH dispense has no items yet.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import moment from 'moment';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import axios from 'axios';
const props = defineProps({
    mohDispense: Object,
});

const processing = ref(false);
const validating = ref(false);

// Computed properties
const totalQuantity = computed(() => {
    if (!props.mohDispense.items) return 0;
    return props.mohDispense.items.reduce((total, item) => total + (item.quantity || 0), 0);
});

// Methods
const getStatusClass = (status) => {
    return {
        'bg-yellow-100 text-yellow-800': status === 'draft',
        'bg-green-100 text-green-800': status === 'processed',
        'bg-red-100 text-red-800': status === 'insufficient_inventory',
    };
};

const formatDate = (date) => {
    return moment(date).format('MMM DD, YYYY');
};

const validateInventory = async () => {
    if (validating.value) return;
    
    validating.value = true;
    
    try {
        const response = await axios.post(route('moh-dispense.validate-inventory', props.mohDispense.id));
        
        if (response.data.success) {
            const validation = response.data.data;
            
            if (validation.can_process) {
                await Swal.fire({
                    title: 'Inventory Validation Passed',
                    text: 'All items have sufficient inventory. You can proceed with processing.',
                    icon: 'success',
                    confirmButtonColor: '#10b981',
                    confirmButtonText: 'OK'
                });
            } else {
                // Show detailed validation results
                const insufficientItems = validation.validation_results.filter(item => !item.sufficient);
                let html = '<div class="text-left"><p class="mb-3">The following items have insufficient inventory:</p><ul class="space-y-2">';
                
                insufficientItems.forEach(item => {
                    html += `<li class="text-sm">
                        <strong>${item.product_name}</strong><br>
                        Required: ${item.required_quantity}, Available: ${item.available_quantity}<br>
                        <span class="text-red-600">Shortage: ${item.shortage}</span>
                    </li>`;
                });
                
                html += '</ul></div>';
                
                await Swal.fire({
                    title: 'Insufficient Inventory',
                    html: html,
                    icon: 'warning',
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'OK'
                });
            }
        }
    } catch (error) {
        console.error('Validation error:', error);
        
        let errorMessage = 'Failed to validate inventory. Please try again.';
        let debugInfo = '';
        
        if (error.response?.data) {
            errorMessage = error.response.data.message || errorMessage;
            if (error.response.data.debug) {
                debugInfo = `\nError: ${error.response.data.debug.error_file}:${error.response.data.debug.error_line}`;
            }
        } else if (error.message) {
            errorMessage = error.message;
        }
        
        await Swal.fire({
            title: 'Validation Error',
            text: errorMessage + debugInfo,
            icon: 'error',
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'OK'
        });
    } finally {
        validating.value = false;
    }
};

const processDispense = async () => {
    if (processing.value) return;
    
    const result = await Swal.fire({
        title: 'Process MOH Dispense',
        text: 'Are you sure you want to process this MOH dispense? This will deduct items from facility inventory.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Process',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    });
    
    if (result.isConfirmed) {
        processing.value = true;
        
        try {
            const response = await axios.post(route('moh-dispense.process', props.mohDispense.id), {}, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                timeout: 300000 // 5 minutes timeout
            });
            
            processing.value = false;
            
            // Update the local status
            props.mohDispense.status = 'processed';
            
            // Show success message
            Swal.fire({
                title: 'Success!',
                text: response.data.message || 'Excel file processed successfully!',
                icon: 'success',
                confirmButtonColor: '#10b981'
            });
            
        } catch (error) {
            processing.value = false;
            
            // Show error message
            let errorMessage = 'Error processing Excel file';
            if (error.response) {
                const errorData = error.response.data;
                errorMessage = errorData.message || errorMessage;
            } else if (error.code === 'ECONNABORTED') {
                errorMessage = 'Processing timeout. Please try again.';
            } else {
                errorMessage = 'Network error. Please check your connection and try again.';
            }
            
            Swal.fire({
                title: 'Error!',
                text: errorMessage,
                icon: 'error',
                confirmButtonColor: '#ef4444'
            });
        }
    }
};

</script>