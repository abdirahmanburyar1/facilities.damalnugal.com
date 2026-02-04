<template>
    <AuthenticatedLayout title="MOH Dispense" description="Manage MOH medication dispensing records">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">MOH Dispense</h1>
            <div class="flex space-x-3">
                <button @click="showUploadModal = true"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Upload Excel
                </button>
                <Link :href="route('moh-dispense.create')"
                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 shadow-sm">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Create New
                </Link>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <div class="flex space-x-4">
                <input v-model="search" type="text" placeholder="Search by MOH number..."
                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2">
                <select v-model="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="processed">Processed</option>
                    <option value="insufficient_inventory">Insufficient Inventory</option>
                </select>
            </div>
        </div>

        <!-- MOH Dispenses Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">MOH Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="mohDispense in mohDispenses.data" :key="mohDispense.id">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <Link :href="route('moh-dispense.show', mohDispense.id)"
                                class="text-blue-600 hover:text-blue-900 font-medium">
                                {{ mohDispense.moh_dispense_number }}
                            </Link>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ mohDispense.created_by?.name || 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ mohDispense.items?.length || 0 }} items
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="getStatusClass(mohDispense.status)"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                {{ mohDispense.status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ formatDate(mohDispense.created_at) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <Link :href="route('moh-dispense.show', mohDispense.id)"
                                    class="text-blue-600 hover:text-blue-900">View</Link>
                                <button v-if="mohDispense.status === 'draft' && mohDispense.excel_file_path"
                                    @click="processDispense(mohDispense.id)"
                                    :disabled="processing"
                                    class="text-green-600 hover:text-green-900 disabled:opacity-50">
                                    {{ processing ? 'Processing...' : 'Process' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            <TailwindPagination :data="mohDispenses" @pagination-change-page="loadPage" />
        </div>

        <!-- Upload Modal - Full Screen -->
        <div v-if="showUploadModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-0"
            @click="closeUploadModal">
            <div class="bg-white w-full h-full overflow-y-auto" @click.stop>
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Upload MOH Dispense Excel</h3>
                        <p class="text-sm text-gray-500 mt-1">Import MOH dispense records from Excel file</p>
                    </div>
                    <button @click="closeUploadModal"
                        class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <!-- Download Template Section -->
                    <div
                        class="mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-green-800">Need a template?</h4>
                                <p class="text-sm text-green-700 mt-1">
                                    Download our template to see the correct format for uploading MOH dispense records.
                                </p>
                                <button @click="downloadTemplate"
                                    class="mt-3 inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-medium text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    Download Template
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Required Columns</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <ul class="space-y-2 text-sm text-gray-600">
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                                    <span class="font-medium">Item</span>
                                    <span class="text-gray-400 ml-2">(required)</span>
                                </li>
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                                    <span class="font-medium">Batch No</span>
                                    <span class="text-gray-400 ml-2">(required)</span>
                                </li>
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                                    <span class="font-medium">Expiry Date</span>
                                    <span class="text-gray-400 ml-2">(required)</span>
                                </li>
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                                    <span class="font-medium">Quantity</span>
                                    <span class="text-gray-400 ml-2">(required)</span>
                                </li>
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                                    <span class="font-medium">Dispense Date</span>
                                    <span class="text-gray-400 ml-2">(required)</span>
                                </li>
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                                    <span class="font-medium">Dispensed By</span>
                                    <span class="text-gray-400 ml-2">(required)</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- File Upload Area -->
                    <div class="mb-6">
                        <div 
                            :class="[
                                'border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer',
                                isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:bg-gray-50'
                            ]"
                            @click="triggerFileInput"
                            @dragover.prevent="handleDragOver"
                            @dragleave.prevent="handleDragLeave"
                            @drop.prevent="handleDrop">
                            <input type="file" ref="fileInput" class="hidden" @change="handleFileSelect"
                                accept=".xlsx,.xls,.csv" />
                            <svg class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                </path>
                            </svg>
                            <p class="text-lg font-medium text-gray-900 mb-2">
                                {{ selectedFile ? 'File Selected' : 'Choose File' }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ selectedFile ? selectedFile.name : 'Click to select or drag and drop file here' }}
                            </p>
                            <p class="text-xs text-gray-400 mt-2">
                                Supports .xlsx, .xls, and .csv files (max 50MB)
                            </p>
                        </div>

                        <!-- Selected File Preview -->
                        <div v-if="selectedFile"
                            class="mt-4 flex items-center justify-between bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-blue-500 mr-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-blue-900">{{ selectedFile.name }}</p>
                                    <p class="text-xs text-blue-700">{{ (selectedFile.size / 1024 / 1024).toFixed(2) }}
                                        MB</p>
                                </div>
                            </div>
                            <button @click.stop="removeFile"
                                class="text-red-500 hover:text-red-700 transition-colors duration-200">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Error Message -->
                        <div v-if="uploadError" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-start">
                                <svg class="h-5 w-5 text-red-400 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-red-800">{{ uploadError }}</p>
                                    <ul v-if="uploadInsufficientItems && uploadInsufficientItems.length" class="mt-2 space-y-1 text-sm text-red-700 list-disc list-inside">
                                        <li v-for="(item, i) in uploadInsufficientItems" :key="i">
                                            {{ item.product_name }}: required {{ item.required_quantity }}, available {{ item.available_quantity }} (short {{ item.shortage }})
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Progress -->
                    <div v-if="uploading" class="mb-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Upload Progress</h4>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-blue-600 h-3 rounded-full transition-all duration-300"
                                :style="{ width: uploadProgress + '%' }"></div>
                        </div>
                        <p class="text-sm text-gray-600 mt-2">{{ uploadProgress }}% complete</p>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 bg-gray-50">
                    <button @click="closeUploadModal"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200">
                        Cancel
                    </button>
                    <button @click="submitUpload"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 border border-transparent rounded-lg font-medium text-sm text-white hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-sm"
                        :disabled="!selectedFile || uploading">
                        <svg v-if="uploading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <svg v-else class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                            </path>
                        </svg>
                        {{ uploading ? 'Uploading...' : 'Upload File' }}
                    </button>
                </div>
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
import axios from 'axios';
import { useToast } from "vue-toastification";

const toast = useToast();

const props = defineProps({
    mohDispenses: Object,
    filters: Object,
});

// Reactive data
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const showUploadModal = ref(false);
const selectedFile = ref(null);
const uploading = ref(false);
const uploadError = ref('');
const uploadInsufficientItems = ref([]);
const uploadProgress = ref(0);
const processing = ref(false);
const fileInput = ref(null);
const isDragging = ref(false);

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

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        validateAndSetFile(file);
    }
};

const validateAndSetFile = (file) => {
    // Check file type
    const extension = '.' + file.name.split('.').pop().toLowerCase();
    const validExtensions = ['.xlsx', '.xls', '.csv'];
    
    if (!validExtensions.includes(extension)) {
        toast.error('Invalid file type. Please upload an Excel file (.xlsx, .xls) or CSV file (.csv)');
        return;
    }
    
    // Check file size (max 50MB)
    const maxSize = 50 * 1024 * 1024; // 50MB
    if (file.size > maxSize) {
        toast.error('File is too large. Maximum file size is 50MB.');
        return;
    }
    
    selectedFile.value = file;
    uploadError.value = '';
};

const removeFile = () => {
    selectedFile.value = null;
    uploadError.value = '';
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const handleDragOver = (event) => {
    event.preventDefault();
    event.stopPropagation();
    isDragging.value = true;
};

const handleDragLeave = (event) => {
    event.preventDefault();
    event.stopPropagation();
    isDragging.value = false;
};

const handleDrop = (event) => {
    event.preventDefault();
    event.stopPropagation();
    isDragging.value = false;
    
    const files = event.dataTransfer.files;
    if (files && files.length > 0) {
        validateAndSetFile(files[0]);
    }
};

const downloadTemplate = () => {
    window.open(route('moh-dispense.download-template'), '_blank');
};

const closeUploadModal = () => {
    showUploadModal.value = false;
    selectedFile.value = null;
    uploadError.value = '';
    uploadInsufficientItems.value = [];
    uploadProgress.value = 0;
    isDragging.value = false;
    uploading.value = false;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submitUpload = async () => {
    if (!selectedFile.value) {
        toast.error('Please select a file to upload');
        return;
    }
    
    uploading.value = true;
    uploadError.value = '';
    uploadProgress.value = 0;
    
    const formData = new FormData();
    formData.append('excel_file', selectedFile.value);
    
    // Show loading toast
    const loadingToast = toast.info('Preparing to upload file...', {
        timeout: false,
        closeOnClick: false,
        draggable: false,
    });
    
    try {
        const response = await axios.post(route('moh-dispense.store'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            timeout: 300000, // 5 minutes timeout
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    uploadProgress.value = Math.round(
                        (progressEvent.loaded * 100) / progressEvent.total
                    );
                }
            },
        });
        
        toast.dismiss(loadingToast);
        closeUploadModal();
        toast.success(`Excel file processed and inventory deducted. MOH Number: ${response.data.moh_dispense_number || 'N/A'}`);
        router.reload({ only: ['mohDispenses'] });
        
    } catch (error) {
        uploading.value = false;
        uploadProgress.value = 0;
        toast.dismiss(loadingToast);
        
        if (error.response) {
            const errorData = error.response.data;
            const errorMessage = errorData.message || 'Upload failed. Please try again.';
            uploadError.value = errorMessage;
            uploadInsufficientItems.value = errorData.insufficient_items || [];
            toast.error(errorMessage);
        } else if (error.code === 'ECONNABORTED') {
            uploadError.value = 'Upload timeout. Please try again.';
            toast.error('Upload timeout. Please try again.');
        } else {
            uploadError.value = 'Network error. Please check your connection.';
            toast.error('Network error. Please check your connection.');
        }
    }
};

const processDispense = async (id) => {
    if (processing.value) return;
    
    processing.value = true;
    
    try {
        const response = await axios.post(route('moh-dispense.process', id), {}, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            timeout: 300000 // 5 minutes timeout
        });
        
        processing.value = false;
        toast.success('MOH Dispense processed successfully!');
        router.reload({ only: ['mohDispenses'] });
        
    } catch (error) {
        processing.value = false;
        
        if (error.response) {
            const errorData = error.response.data;
            toast.error('Error: ' + (errorData.message || 'Unknown error'));
        } else {
            toast.error('Network error. Please try again.');
        }
    }
};

const loadPage = (page) => {
    router.get(route('moh-dispense.index'), {
        search: search.value,
        status: statusFilter.value,
        page: page
    }, {
        preserveState: true,
        replace: true
    });
};

// Watchers
watch([search, statusFilter], () => {
    router.get(route('moh-dispense.index'), {
        search: search.value,
        status: statusFilter.value
    }, {
        preserveState: true,
        replace: true
    });
}, { deep: true });
</script>