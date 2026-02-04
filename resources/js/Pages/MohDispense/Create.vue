<template>
    <AuthenticatedLayout title="Create MOH Dispense" description="Upload Excel file to create MOH dispense records">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Create MOH Dispense</h1>
                <p class="mt-1 text-sm text-gray-600">Upload an Excel file to create MOH dispense records</p>
            </div>

            <!-- Upload Form -->
            <div class="bg-white rounded-lg shadow p-6">
                <form @submit.prevent="submitForm">
                    <!-- File Upload -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Excel File
                        </label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400 transition-colors">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="excel_file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                        <span>Upload a file</span>
                                        <input id="excel_file" name="excel_file" type="file" 
                                            class="sr-only" 
                                            accept=".xlsx,.xls,.csv"
                                            @change="handleFileSelect"
                                            ref="fileInput">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">Excel files (.xlsx, .xls, .csv) up to 10MB</p>
                            </div>
                        </div>
                        
                        <!-- Selected File Display -->
                        <div v-if="selectedFile" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm font-medium text-green-800">{{ selectedFile.name }}</span>
                                <span class="ml-2 text-sm text-green-600">({{ formatFileSize(selectedFile.size) }})</span>
                                <button type="button" @click="removeFile" 
                                    class="ml-auto text-red-600 hover:text-red-800">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Error Display -->
                        <div v-if="uploadError" class="mt-2 text-sm text-red-600">
                            {{ uploadError }}
                        </div>
                    </div>

                    <!-- Template Download -->
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h3 class="text-sm font-medium text-blue-800 mb-2">Excel File Format</h3>
                        <p class="text-sm text-blue-700 mb-3">
                            Your Excel file should contain the following columns:
                        </p>
                        <ul class="text-sm text-blue-700 list-disc list-inside mb-3">
                            <li><strong>item</strong> - Product name, ID, or productID</li>
                            <li><strong>batch_no</strong> - Batch number from the medication package</li>
                            <li><strong>expiry_date</strong> - Expiry date (YYYY-MM-DD format)</li>
                            <li><strong>quantity</strong> - Quantity dispensed (number only)</li>
                            <li><strong>dispense_date</strong> - Date of dispense (YYYY-MM-DD format)</li>
                            <li><strong>dispensed_by</strong> - Name of person who dispensed</li>
                        </ul>
                        <button type="button" @click="downloadTemplate"
                            class="inline-flex items-center px-3 py-2 border border-blue-300 text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 rounded-lg">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Download Template
                        </button>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3">
                        <Link :href="route('moh-dispense.index')"
                            class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
                            Cancel
                        </Link>
                        <button type="submit" :disabled="!selectedFile || processing"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ processing ? 'Uploading...' : 'Upload File' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

// Reactive data
const selectedFile = ref(null);
const processing = ref(false);
const uploadError = ref('');
const fileInput = ref(null);

// Methods
const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        selectedFile.value = file;
        uploadError.value = '';
    }
};

const removeFile = () => {
    selectedFile.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const downloadTemplate = () => {
    window.open(route('moh-dispense.download-template'), '_blank');
};

const submitForm = async () => {
    if (!selectedFile.value) return;
    
    processing.value = true;
    uploadError.value = '';
    
    const formData = new FormData();
    formData.append('excel_file', selectedFile.value);
    
    try {
        const response = await axios.post(route('moh-dispense.store'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            timeout: 300000, // 5 minutes timeout
        });
        
        // Success - redirect to the created MOH dispense
        if (response.data.moh_dispense_id) {
            router.visit(route('moh-dispense.show', response.data.moh_dispense_id));
        } else {
            router.visit(route('moh-dispense.index'));
        }
        
    } catch (error) {
        processing.value = false;
        
        if (error.response) {
            const errorData = error.response.data;
            uploadError.value = errorData.message || 'Upload failed. Please try again.';
        } else if (error.code === 'ECONNABORTED') {
            uploadError.value = 'Upload timeout. Please try again.';
        } else {
            uploadError.value = 'Network error. Please check your connection.';
        }
    }
};
</script>