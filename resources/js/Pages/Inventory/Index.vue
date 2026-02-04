<script setup>
import {
    ref,
    watch,
    computed,
    onUnmounted
} from "vue";
import { Head, router, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import { useToast } from "vue-toastification";
import axios from "axios";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";
import "@/Components/multiselect.css";
import moment from "moment";
import { TailwindPagination } from "laravel-vue-pagination";

const toast = useToast();

const props = defineProps({
    inventories: Object,
    category: Array,
    filters: Object,
    inventoryStatusCounts: Object,
});

// Search and filter states
const search = ref(props.filters?.search || '');

const category = ref(props.filters?.category || '');
const status = ref(props.filters?.status || '');
const per_page = ref(props.filters?.per_page || 25);

const isLoading = ref(false);
const filterTimeout = ref(null);

// Modal states
const showLegend = ref(false);

// Upload states
const showUploadModal = ref(false);
const selectedFile = ref(null);
const fileInput = ref(null);
const isUploading = ref(false);
const uploadProgress = ref(0);
const uploadResults = ref(null);
const importId = ref(null);

// Apply filters with debouncing
const applyFilters = () => {
    // Clear any existing timeout
    if (filterTimeout.value) {
        clearTimeout(filterTimeout.value);
    }

    // Set a new timeout to debounce the filter application
    filterTimeout.value = setTimeout(() => {
        const query = {};

        // Only add non-empty filter values to query object
        if (search.value && search.value.trim()) query.search = search.value.trim();

        if (category.value && category.value !== '') query.category = category.value;
        if (status.value && status.value !== '') query.status = status.value;

        // Always include per_page in query if it exists
        if (per_page.value) query.per_page = per_page.value;
        if (props.filters?.page) query.page = props.filters.page;

        isLoading.value = true;

        router.get(route("inventories.index"), query, {
            preserveState: true,
            preserveScroll: true,
            only: [
                "inventories",
                "products",
                "inventoryStatusCounts",
                "category",
            ],
            onFinish: () => {
                isLoading.value = false;
            },
            onError: (errors) => {
                isLoading.value = false;

                // Provide more specific error messages
                if (errors && typeof errors === 'object') {
                    const errorMessages = Object.values(errors).flat();
                    if (errorMessages.length > 0) {
                        toast.error(`Filter error: ${errorMessages[0]}`);
                    } else {
                        toast.error('Failed to apply filters - please try again');
                    }
                } else if (typeof errors === 'string') {
                    toast.error(`Filter error: ${errors}`);
                } else {
                    toast.error('Failed to apply filters - please try again');
                }
            }
        });
    }, 300); // 300ms debounce delay
};

// Watch for filter changes with debouncing
watch(
    [
        search,
        category,
        status,
        per_page
    ],
    (newValues, oldValues) => {
        // Skip if this is the initial load
        if (oldValues && oldValues.length > 0) {
            // Reset page to 1 when filters change (except per_page)
            if (newValues[0] !== oldValues[0] || // search
                newValues[1] !== oldValues[1] || // category
                newValues[2] !== oldValues[2] || // status
                newValues[3] !== oldValues[3]) { // per_page
                props.filters.page = 1;
            }
            applyFilters();
        }
    },
    { deep: true }
);

function formatQty(qty) {
    // Ensure qty is a valid number for proper sorting
    const num = Number(qty);

    // Handle edge cases for sorting
    if (qty === null || qty === undefined) {
        return '0';
    }

    if (isNaN(num) || !isFinite(num)) {
        return '0';
    }

    // Ensure negative quantities are handled properly
    if (num < 0) {
        return '0';
    }

    // Format with proper number formatting
    return Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(num);
}

// Update hasActiveFilters to remove sorting
const hasActiveFilters = computed(() => {
    return search.value || category.value || status.value; // Remove sorting checks
});

// Update clearFilters to remove sorting reset
const clearFilters = () => {
    // Clear all filter values
    search.value = "";

    category.value = "";
    status.value = "";

    // Reset pagination
    if (props.filters) {
        props.filters.page = 1;
    }
    per_page.value = 25; // Reset per_page to default

    // Apply filters immediately without debouncing
    const query = { per_page: per_page.value, page: 1 };

    isLoading.value = true;

    router.get(route("inventories.index"), query, {
        preserveState: true,
        preserveScroll: true,
        only: [
            "inventories",
            "products",
            "inventoryStatusCounts",
            "category",
        ],
        onFinish: () => {
            isLoading.value = false;
        },
        onError: (errors) => {
            isLoading.value = false;
            console.error('❌ Error clearing filters:', errors);
            toast.error('Failed to clear filters - please try again');
        }
    });

    toast.success("Filters cleared!");
};

// Upload modal functions
const openUploadModal = () => {
    showUploadModal.value = true;
};

const closeUploadModal = () => {
    showUploadModal.value = false;
    selectedFile.value = null;
    uploadProgress.value = 0;
    uploadResults.value = null;
    if (fileInput.value) {
        fileInput.value.value = null;
    }
};

const triggerFileInput = () => {
    fileInput.value.click();
};

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    // Check file type - allow .xlsx and .csv
    const fileExtension = "." + file.name.split(".").pop().toLowerCase();
    const validExtensions = [".xlsx", ".csv"];

    if (!validExtensions.includes(fileExtension)) {
        toast.error(
            "Invalid file type. Please upload an Excel file (.xlsx) or CSV file (.csv)"
        );
        event.target.value = null; // Clear the file input
        selectedFile.value = null;
        return;
    }

    // Check file size (max 5MB)
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        toast.error("File is too large. Maximum file size is 5MB.");
        event.target.value = null;
        selectedFile.value = null;
        return;
    }

    selectedFile.value = file;
};

const removeSelectedFile = () => {
    selectedFile.value = null;
    if (fileInput.value) {
        fileInput.value.value = null;
    }
};

const uploadFile = async () => {
    if (!selectedFile.value) {
        toast.error("Please select a file to upload");
        return;
    }

    // Show loading toast
    const loadingToast = toast.info("Preparing to upload file...", {
        timeout: false,
        closeOnClick: false,
        draggable: false,
    });

    isUploading.value = true;
    uploadProgress.value = 0;
    const formData = new FormData();
    formData.append("file", selectedFile.value);

    await axios.post(
        route("inventories.import"),
        formData,
        {
            headers: {
                "Content-Type": "multipart/form-data",
            },
            onUploadProgress: (progressEvent) => {
                uploadProgress.value = Math.round(
                    (progressEvent.loaded * 100) / progressEvent.total
                );
            },
        }
    )
        .then(response => {
            isUploading.value = false;
            uploadResults.value = response.data;
            toast.dismiss(loadingToast);
            toast.success(response.data.message || "File uploaded successfully!");

            // Refresh inventory data
            applyFilters();
        })
        .catch(error => {
            isUploading.value = false;
            console.error('Upload error:', error);
            closeUploadModal();
            toast.dismiss(loadingToast);
            toast.error(error.response?.data?.message || "Failed to upload file");
        });
};

// Download template function
const downloadTemplate = () => {
    // Create a CSV format that Excel can open properly
    const headers = ['Item', 'Category', 'UoM', 'Source', 'Quantity', 'Batch No', 'Expiry Date', 'Location', 'Warehouse', 'Unit Cost'];
    const sampleData = [
        ['Acetylsalicylic acid (aspirin)75mg tab', 'Drugs', 'Pcs', 'UNICEF', '2300', 'ADF345342', '20/02/2028', 'W1-R1-C1-R1', 'Nugal Regional Main Warehouse', '0.4']
    ];

    // Create CSV content with headers + sample row
    const csvContent = [headers, ...sampleData]
        .map(row => row.map(field => `"${field}"`).join(','))
        .join('\n');

    // Create blob with CSV MIME type
    const blob = new Blob([csvContent], {
        type: 'text/csv;charset=utf-8;'
    });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', 'inventory_import_template.csv');
    link.style.visibility = 'hidden';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    // Clean up the URL object
    URL.revokeObjectURL(url);

    toast.success('Template downloaded successfully! Open with Excel to use.');
};

// Format date
const formatDate = (date) => {
    // Handle null/undefined dates for proper sorting
    if (!date || date === null || date === undefined) {
        return "";
    }

    // Handle empty string dates
    if (date === '') {
        return "";
    }

    try {
        // Ensure proper date parsing for sorting
        const parsedDate = moment(date);

        // Validate the parsed date
        if (!parsedDate.isValid()) {
            console.warn(`Invalid date value: ${date}, returning empty string`);
            return "";
        }

        // Return formatted date for display
        return parsedDate.format("DD/MM/YYYY");
    } catch (error) {
        console.error(`Error formatting date: ${date}`, error);
        return "";
    }
};

// Helpers
function getTotalQuantity(inventory) {
    if (!inventory?.inventories || !Array.isArray(inventory.inventories)) {
        return 0;
    }

    const total = inventory.inventories.reduce((sum, facilityInventory) => {
        if (!facilityInventory?.items || !Array.isArray(facilityInventory.items)) {
            return sum;
        }

        const facilityTotal = facilityInventory.items.reduce((facilitySum, item) => {
            // Ensure proper numeric conversion for sorting
            let quantity = 0;

            if (item.quantity !== null && item.quantity !== undefined) {
                const num = Number(item.quantity);

                // Handle invalid numbers
                if (isNaN(num) || !isFinite(num)) {
                    console.warn(`Invalid quantity for item ${item.id}: ${item.quantity}, treating as 0`);
                    quantity = 0;
                } else if (num < 0) {
                    console.warn(`Negative quantity for item ${item.id}: ${num}, treating as 0`);
                    quantity = 0;
                } else {
                    quantity = num;
                }
            }

            return facilitySum + quantity;
        }, 0);

        return sum + facilityTotal;
    }, 0);

    // Final validation
    if (isNaN(total) || !isFinite(total)) {
        console.warn(`Invalid total quantity calculated: ${total}, returning 0`);
        return 0;
    }

    return total;
}

// Status calculation based on reorder level logic
const getInventoryStatus = (inventory) => {
    const totalQuantity = getTotalQuantity(inventory);
    const reorderLevel = Number(inventory.reorder_level) || 0;

    // Check if completely out of stock first
    if (totalQuantity === 0) {
        return 'out_of_stock';
    }

    if (reorderLevel <= 0) {
        // No reorder level set, default to in stock
        return 'in_stock';
    }

    // Calculate the low stock threshold (reorder level + 30%)
    const lowStockThreshold = reorderLevel * 1.3;

    if (totalQuantity > 0 && totalQuantity <= reorderLevel) {
        // Items at or below reorder level (critical)
        return 'low_stock_reorder_level';
    } else if (totalQuantity > reorderLevel && totalQuantity <= lowStockThreshold) {
        // Items between reorder level and reorder level + 30% (low stock)
        return 'low_stock';
    } else {
        // Items above reorder level + 30% (in stock)
        return 'in_stock';
    }
};

// Needs reorder: use new status logic
function needsReorder(inventory) {
    const status = getInventoryStatus(inventory);
    return status === 'low_stock_reorder_level' || status === 'low_stock' || status === 'out_of_stock';
}

// Computed properties for inventory status counts - from backend data (not paginated)
const inStockCount = computed(() => {
    if (!props.inventoryStatusCounts || !Array.isArray(props.inventoryStatusCounts)) return 0;
    const stat = props.inventoryStatusCounts.find(s => s.status === 'in_stock');
    return stat ? stat.count : 0;
});

const lowStockCount = computed(() => {
    if (!props.inventoryStatusCounts || !Array.isArray(props.inventoryStatusCounts)) return 0;
    const stat = props.inventoryStatusCounts.find(s => s.status === 'low_stock');
    return stat ? stat.count : 0;
});

const lowStockReorderLevelCount = computed(() => {
    if (!props.inventoryStatusCounts || !Array.isArray(props.inventoryStatusCounts)) return 0;
    const stat = props.inventoryStatusCounts.find(s => s.status === 'low_stock_reorder_level');
    return stat ? stat.count : 0;
});

const outOfStockCount = computed(() => {
    if (!props.inventoryStatusCounts || !Array.isArray(props.inventoryStatusCounts)) return 0;
    const stat = props.inventoryStatusCounts.find(s => s.status === 'out_of_stock');
    return stat ? stat.count : 0;
});

// Combined count for debugging - shows both old and new status names
const combinedReorderLevelCount = computed(() => {
    if (!props.inventoryStatusCounts || !Array.isArray(props.inventoryStatusCounts)) return 0;
    // Just return the low_stock_reorder_level count (no need to double it)
    const lowStockReorderLevel = props.inventoryStatusCounts.find(s => s.status === 'low_stock_reorder_level')?.count || 0;
    return lowStockReorderLevel;
});

function getResults(page = 1) {
    if (props.filters) {
        props.filters.page = page;
    }
    applyFilters();
}

// Cleanup function to clear timeout when component unmounts
onUnmounted(() => {
    if (filterTimeout.value) {
        clearTimeout(filterTimeout.value);
    }
});

</script>

<template>

    <Head title="Inventory Management" />

    <AuthenticatedLayout img="/assets/images/inventory.png" title="Management Your Inventory"
        description="Keeping Essentials Ready, Every Time">
        <div class="mb-[100px]">
            <!-- Header & Actions -->
            <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Facility Inventory</h1>
                    <p class="text-sm text-gray-600 mt-1">Manage your facility's inventory items and track stock levels</p>
                </div>
                <div class="flex flex-wrap gap-2 md:gap-4 items-center">
                    <button @click="openUploadModal"
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 border border-transparent rounded-lg font-medium text-sm text-white hover:from-amber-600 hover:to-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition-all duration-200 shadow-sm"
                        :disabled="isUploading">
                        <svg v-if="!isUploading" class="h-4 w-4 mr-2" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                            </path>
                        </svg>
                        <svg v-else class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        {{ isUploading ? 'Uploading...' : 'Upload Excel' }}
                    </button>
                </div>
            </div>
            
            <!-- Filters Card -->
            <div class="bg-white rounded-xl shadow-md p-4 mb-4 border border-gray-200">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
                    <div class="col-span-1 md:col-span-2 min-w-0">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search Items</label>
                        <div class="relative">
                            <input v-model="search" type="text"
                                class="w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 pr-10"
                                placeholder="Search by item name, barcode, batch number, uom" />
                            <button @click="applyFilters" 
                                class="absolute inset-y-0 right-0 px-3 text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="col-span-1 min-w-0">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <Multiselect v-model="category" :options="props.category || []" :searchable="true"
                            :close-on-select="true" :show-labels="false" placeholder="Select a category"
                            :allow-empty="true" class="multiselect--with-icon w-full" />
                    </div>
                    <div class="col-span-1 min-w-0">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select v-model="status" @change="applyFilters"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Statuses ({{ props.inventories?.data?.length || 0 }})</option>
                            <option value="in_stock">✅ In Stock ({{ inStockCount }})</option>
                            <option value="low_stock">⚠️ Low Stock ({{ lowStockCount }})</option>
                            <option value="low_stock_reorder_level">🚨 Low Stock + Reorder Level ({{ lowStockReorderLevelCount }})</option>
                            <option value="out_of_stock">❌ Out of Stock ({{ outOfStockCount }})</option>
                        </select>
                    </div>
                </div>

                <!-- Remove debug info for sorting since sorting is removed -->

                <!-- Active Filters Display -->
                <div v-if="hasActiveFilters" class="mt-4 flex flex-wrap gap-2 items-center">
                    <span class="text-sm font-medium text-gray-700">Active Filters:</span>
                    
                    <!-- Search Filter Badge -->
                    <span v-if="search"
                        class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                        <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Search: {{ search }}
                        <button @click="search = ''" class="ml-2 text-blue-600 hover:text-blue-800 hover:bg-blue-200 rounded-full p-0.5 transition-colors">×</button>
                    </span>

                    <!-- Category Filter Badge -->
                    <span v-if="category"
                        class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                        <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        Category: {{ category }}
                        <button @click="category = ''" class="ml-2 text-yellow-600 hover:text-yellow-800 hover:bg-yellow-200 rounded-full p-0.5 transition-colors">×</button>
                    </span>
                    
                    <!-- Status Filter Badge -->
                    <span v-if="status"
                        class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                        <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Status: {{
                            status === 'in_stock' ? 'In Stock' :
                                status === 'low_stock' ? '⚠️ Low Stock' :
                                    status === 'low_stock_reorder_level' ? 'Low Stock + Reorder Level' :
                                        status === 'out_of_stock' ? 'Out of Stock' :
                                            status
                        }}
                        <button @click="status = ''" class="ml-2 text-red-600 hover:text-red-800 hover:bg-red-200 rounded-full p-0.5 transition-colors">×</button>
                    </span>
                    
                    <!-- Clear All Button -->
                    <button @click="clearFilters"
                        class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700 hover:bg-gray-300 border border-gray-300 transition-colors">
                        <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Clear All
                    </button>
                </div>

                <!-- Controls Row -->
                <div class="flex justify-between items-center gap-4 mt-4">
                    <!-- Filter Status -->
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <div v-if="isLoading" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Applying filters...</span>
                        </div>
                        <div v-else-if="hasActiveFilters" class="flex items-center gap-2 text-green-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Filters active</span>
                        </div>
                    </div>
                    
                    <!-- Right side controls -->
                    <div class="flex items-center gap-4">
                        <select v-model="per_page" 
                            @change="() => { 
                                if (props.filters) props.filters.page = 1; 
                                applyFilters(); 
                            }"
                            class="rounded-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-[200px]">
                            <option value="25">25 per page</option>
                            <option value="50">50 per page</option>
                            <option value="100">100 per page</option>
                            <option value="200">200 per page</option>
                        </select>
                        <button @click="showLegend = true"
                            class="px-3 py-2 bg-blue-100 text-blue-700 rounded-full flex items-center gap-2 hover:bg-blue-200 transition-colors border border-blue-200"
                            title="Icon Legend">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table and Sidebar -->
            <div class="grid grid-cols-1 lg:grid-cols-8 gap-6">
                <!-- Main Table -->
                <div class="lg:col-span-7">

                    <div class="bg-white rounded-xl overflow-hidden">
                        <table class="w-full overflow-hidden text-sm text-left table-sm rounded-t-lg">
                            <thead>
                                <tr style="background-color: #F4F7FB;">
                                    <th class="px-3 py-2 text-xs font-bold rounded-tl-lg w-48"
                                        style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">
                                        <div class="flex items-center justify-between">
                                            <span>Item</span>
                                        </div>
                                    </th>
                                    <th class="px-3 py-2 text-xs font-bold"
                                        style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">Category
                                    </th>
                                    <th class="px-3 py-2 text-xs font-bold"
                                        style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">UoM</th>
                                    <th class="px-3 py-2 text-xs font-bold text-center"
                                        style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" colspan="3">Item
                                        Details</th>
                                    <th class="px-3 py-2 text-xs font-bold text-center"
                                        style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">Total QTY
                                        on Hand</th>
                                    <th class="px-3 py-2 text-xs font-bold text-center"
                                        style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">Status
                                    </th>
                                    <th class="px-3 py-2 text-xs font-bold"
                                        style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">Reorder
                                        Level</th>
                                    <th class="px-3 py-2 text-xs font-bold"
                                        style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">Actions
                                    </th>
                                </tr>
                                <tr style="background-color: #F4F7FB;">
                                    <th class="px-2 py-2 text-xs font-bold border border-[#B7C6E6] text-center"
                                        style="color: #4F6FCB;">
                                        <div class="flex items-center justify-center gap-1">
                                            <span>QTY</span>
                                        </div>
                                    </th>
                                    <th class="px-2 py-1 text-xs font-bold border border-[#B7C6E6] text-center"
                                        style="color: #4F6FCB;">Batch Number</th>
                                    <th class="px-2 py-1 text-xs font-bold border border-[#B7C6E6] text-center"
                                        style="color: #4F6FCB;">
                                        <div class="flex items-center justify-center gap-1">
                                            <span>Expiry Date</span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="isLoading">
                                    <tr>
                                        <td colspan="11" class="text-center py-8 text-gray-500 bg-gray-50">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <svg class="animate-spin h-10 w-10 text-gray-300"
                                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                                        stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                    </path>
                                                </svg>
                                                <span class="text-sm font-medium text-gray-600">
                                                    {{ isLoading ? 'Applying filters...' : 'Loading inventory data...'
                                                    }}
                                                </span>
                                                <span class="text-xs text-gray-400">Please wait...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <template
                                    v-else-if="!props.inventories || !props.inventories.data || props.inventories.data.length === 0">
                                    <tr>
                                        <td colspan="11" class="text-center py-8 text-gray-500 bg-gray-50">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-300"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 17v-2a4 4 0 118 0v2m-4 4a4 4 0 01-4-4H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-2a4 4 0 01-4 4z" />
                                                </svg>
                                                <span>{{ !props.inventories ? 'Loading...' : 'No inventory data found.' }}</span>
                                                <div class="text-xs text-gray-400 mt-2">
                                                    {{ totalProducts > 0 ? `Total products: ${totalProducts}` : 'No products available' }}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <template v-else v-for="inventory in props.inventories.data" :key="inventory.id">
                                    <!-- Show all products, but handle 0-quantity items differently -->
                                    <template v-if="inventory.inventories && inventory.inventories.length > 0">
                                        <!-- Show all items including 0 quantity -->
                                        <tr v-for="(item, itemIndex) in inventory.inventories.flatMap(inv => inv.items)"
                                            :key="`${inventory.id}-${item.id}`"
                                            class="hover:bg-gray-50 transition-colors duration-150 border-b items-center"
                                            style="border-bottom: 1px solid #B7C6E6;">
                                            <!-- Item Name - only on first row for this inventory -->
                                            <td v-if="itemIndex === 0"
                                                :rowspan="inventory.inventories.flatMap(inv => inv.items).length"
                                                class="px-3 py-2 text-xs font-medium text-gray-800 align-middle items-center">
                                                {{ inventory.name }}</td>

                                            <!-- Category - only on first row for this inventory -->
                                            <td v-if="itemIndex === 0"
                                                :rowspan="inventory.inventories.flatMap(inv => inv.items).length"
                                                class="px-3 py-2 text-xs text-gray-700 align-middle items-center">{{
                                                    inventory.category?.name }}</td>

                                            <!-- UoM - only on first row for this inventory -->
                                            <td v-if="itemIndex === 0"
                                                :rowspan="inventory.inventories.flatMap(inv => inv.items).length"
                                                class="px-3 py-2 text-xs text-gray-700 align-middle items-center">{{
                                                    inventory.inventories.flatMap(inv => inv.items)[0]?.uom || 'No UoM'}}
                                            </td>

                                            <!-- QTY -->
                                            <td class="px-2 py-1 text-xs border-b border-[#B7C6E6] items-center align-middle"
                                                :class="(item.quantity || 0) > 0 ? 'text-gray-900' : 'text-gray-400'">
                                                {{ formatQty(item.quantity || 0) }}</td>

                                            <!-- Batch Number -->
                                            <td class="px-2 py-1 text-xs border-b border-[#B7C6E6] items-center align-middle"
                                                :class="(item.quantity || 0) > 0 ? 'text-gray-900' : 'text-gray-400'">
                                                {{ item.batch_number || 'No Batch' }}</td>

                                            <!-- Expiry Date -->
                                            <td class="px-2 py-1 text-xs border-b border-[#B7C6E6] items-center align-middle"
                                                :class="(item.quantity || 0) > 0 ? 'text-gray-900' : 'text-gray-400'">
                                                {{ formatDate(item.expiry_date) || 'No Expiry' }}</td>


                                            <!-- Total QTY on Hand - only on first row for this inventory -->
                                            <td v-if="itemIndex === 0"
                                                :rowspan="inventory.inventories.flatMap(inv => inv.items).length"
                                                class="px-3 py-2 text-xs text-gray-800 align-middle items-center">
                                                <div class="flex items-center justify-center">
                                                    <span class="font-medium text-lg">{{
                                                        formatQty(getTotalQuantity(inventory)) }}</span>
                                                </div>
                                            </td>

                                            <!-- Status - only on first row for this inventory -->
                                            <td v-if="itemIndex === 0"
                                                :rowspan="inventory.inventories.flatMap(inv => inv.items).length"
                                                class="px-3 py-2 text-xs text-gray-800 text-center align-middle">
                                                <div class="flex items-center justify-center space-x-2 w-full">
                                                    <!-- Main status icon -->
                                                    <div v-if="getInventoryStatus(inventory) === 'in_stock'"
                                                        class="flex items-center justify-center"
                                                        title="In Stock - No reorder needed">
                                                        <img src="/assets/images/in_stock.png" alt="In Stock"
                                                            class="w-8 h-8 drop-shadow-sm" />
                                                    </div>
                                                    <div v-else-if="getInventoryStatus(inventory) === 'low_stock'"
                                                        class="flex items-center justify-center"
                                                        title="⚠️ Low Stock - Reorder recommended">
                                                        <img src="/assets/images/low_stock.png" alt="Low Stock"
                                                            class="w-8 h-8 drop-shadow-sm" />
                                                    </div>
                                                    <div v-else-if="getInventoryStatus(inventory) === 'low_stock_reorder_level'"
                                                        class="flex items-center justify-center"
                                                        title="Low Stock + Reorder Level - Immediate reorder needed">
                                                        <img src="/assets/images/low_stock.png"
                                                            alt="Low Stock + Reorder Level"
                                                            class="w-8 h-8 drop-shadow-sm" />
                                                    </div>
                                                    <div v-else-if="getInventoryStatus(inventory) === 'out_of_stock'"
                                                        class="flex items-center justify-center"
                                                        title="Out of Stock - Immediate reorder needed">
                                                        <img src="/assets/images/out_stock_alert.png" alt="Out of Stock"
                                                            class="w-8 h-8 drop-shadow-sm" />
                                                    </div>
                                                    <div v-else class="flex items-center justify-center"
                                                        title="Status Unknown">
                                                        <div
                                                            class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                                            <span class="text-xs text-gray-500">?</span>
                                                        </div>
                                                    </div>

                                                    <!-- Reorder Level Icon (shows for all items that need reordering) -->
                                                    <div v-if="needsReorder(inventory)"
                                                        class="flex items-center justify-center"
                                                        title="Reorder Action Required">
                                                        <img src="/assets/images/reorder_level.png"
                                                            alt="Reorder Required" class="w-8 h-8 drop-shadow-sm" />
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Reorder Level - only on first row for this inventory -->
                                            <td v-if="itemIndex === 0"
                                                :rowspan="inventory.inventories.flatMap(inv => inv.items).length"
                                                class="px-3 py-2 text-xs text-gray-800 align-middle items-center">
                                                <div class="flex flex-col items-center space-y-1">
                                                    <div class="font-medium">{{ formatQty(inventory.reorder_level || 0)
                                                        }}</div>
                                                </div>
                                            </td>

                                            <!-- Actions - only on first row for this inventory -->
                                            <td v-if="itemIndex === 0"
                                                :rowspan="inventory.inventories.flatMap(inv => inv.items).length"
                                                class="px-3 py-2 text-xs text-gray-800 align-middle items-center">
                                                <div class="flex flex-col items-center justify-center space-y-2">
                                                    <!-- Reorder Button for Low Stock, Reorder Level, and Out of Stock Items -->
                                                    <div v-if="needsReorder(inventory)"
                                                        class="flex flex-col items-center">
                                                        <button
                                                            class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center border-2 border-blue-200 hover:bg-blue-200 transition-colors"
                                                            title="Reorder - {{ getInventoryStatus(inventory) === 'low_stock' ? 'Low Stock' : getInventoryStatus(inventory) === 'low_stock_reorder_level' ? 'Low Stock + Reorder Level' : 'Out of Stock' }}">
                                                            <svg class="w-4 h-4 text-blue-600" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                                                </path>
                                                            </svg>
                                                        </button>
                                                    </div>

                                                    <!-- Future actions can be added here -->
                                                </div>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Show products with no inventory items as a single row -->
                                    <template v-else>
                                        <tr class="hover:bg-gray-50 transition-colors duration-150 border-b items-center"
                                            style="border-bottom: 1px solid #B7C6E6;">
                                            <!-- Item Name -->
                                            <td
                                                class="px-3 py-2 text-xs font-medium text-gray-800 align-middle items-center">
                                                {{ inventory.name }}</td>

                                            <!-- Category -->
                                            <td class="px-3 py-2 text-xs text-gray-700 align-middle items-center">{{
                                                inventory.category?.name }}</td>

                                            <!-- UoM -->
                                            <td class="px-3 py-2 text-xs text-gray-700 align-middle items-center">
                                                <span class="text-gray-400">No UoM</span>
                                            </td>

                                            <!-- QTY -->
                                            <td
                                                class="px-2 py-1 text-xs border-b border-[#B7C6E6] items-center align-middle text-gray-400">
                                                <span class="text-gray-400">No Items</span>
                                            </td>

                                            <!-- Batch Number -->
                                            <td
                                                class="px-2 py-1 text-xs border-b border-[#B7C6E6] items-center align-middle text-gray-400">
                                                <span class="text-gray-400">No Batch</span>
                                            </td>

                                            <!-- Expiry Date -->
                                            <td
                                                class="px-2 py-1 text-xs border-b border-[#B7C6E6] items-center align-middle text-gray-400">
                                                <span class="text-gray-400">No Expiry</span>
                                            </td>

                                            <!-- Total QTY on Hand -->
                                            <td class="px-3 py-2 text-xs text-gray-800 align-middle items-center">
                                                <div class="flex items-center justify-center">
                                                    <span class="font-medium text-lg text-gray-400">{{
                                                        formatQty(getTotalQuantity(inventory)) }}</span>
                                                </div>
                                            </td>

                                            <!-- Status -->
                                            <td class="px-2 py-1 text-xs text-gray-800 text-center align-middle">
                                                <div class="flex items-center justify-center space-x-2 w-full">
                                                    <!-- Main status icon -->
                                                    <div v-if="getInventoryStatus(inventory) === 'in_stock'"
                                                        class="flex items-center justify-center"
                                                        title="In Stock - No reorder needed">
                                                        <img src="/assets/images/in_stock.png" alt="In Stock"
                                                            class="w-8 h-8 drop-shadow-sm" />
                                                    </div>
                                                    <div v-else-if="getInventoryStatus(inventory) === 'low_stock'"
                                                        class="flex items-center justify-center"
                                                        title="⚠️ Low Stock - Reorder recommended">
                                                        <img src="/assets/images/low_stock.png" alt="Low Stock"
                                                            class="w-8 h-8 drop-shadow-sm" />
                                                    </div>
                                                    <div v-else-if="getInventoryStatus(inventory) === 'low_stock_reorder_level'"
                                                        class="flex items-center justify-center"
                                                        title="Low Stock + Reorder Level - Immediate reorder needed">
                                                        <img src="/assets/images/low_stock.png"
                                                            alt="Low Stock + Reorder Level"
                                                            class="w-8 h-8 drop-shadow-sm" />
                                                    </div>
                                                    <div v-else-if="getInventoryStatus(inventory) === 'out_of_stock'"
                                                        class="flex items-center justify-center"
                                                        title="Out of Stock - Immediate reorder needed">
                                                        <img src="/assets/images/out_stock_alert.png" alt="Out of Stock"
                                                            class="w-8 h-8 drop-shadow-sm" />
                                                    </div>
                                                    <div v-else class="flex items-center justify-center"
                                                        title="Status Unknown">
                                                        <div
                                                            class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                                            <span class="text-xs text-gray-500">?</span>
                                                        </div>
                                                    </div>

                                                    <!-- Reorder Level Icon (shows for all items that need reordering) -->
                                                    <div v-if="needsReorder(inventory)"
                                                        class="flex items-center justify-center"
                                                        title="Reorder Action Required">
                                                        <img src="/assets/images/reorder_level.png"
                                                            alt="Reorder Required" class="w-8 h-8 drop-shadow-sm" />
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Reorder Level -->
                                            <td class="px-3 py-2 text-xs text-gray-800 align-middle items-center">
                                                <div class="flex flex-col items-center space-y-1">
                                                    <div class="font-medium">{{ formatQty(inventory.reorder_level || 0)
                                                        }}</div>
                                                </div>
                                            </td>

                                            <!-- Actions -->
                                            <td class="px-3 py-2 text-xs text-gray-800 align-middle items-center">
                                                <div class="flex flex-col items-center justify-center space-y-2">
                                                    <!-- Reorder Button for Low Stock, Reorder Level, and Out of Stock Items -->
                                                    <div v-if="needsReorder(inventory)"
                                                        class="flex flex-col items-center">
                                                        <Link :href="route('orders.create')"
                                                            class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center border-2 border-blue-200 hover:bg-blue-200 transition-colors">
                                                        <svg class="w-4 h-4 text-blue-600" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                                            </path>
                                                        </svg>
                                                        </Link>
                                                    </div>

                                                    <!-- Future actions can be added here -->
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                            </tbody>
                        </table>

                        <div class="mt-2 flex justify-between">
                            <div class="text-xs text-gray-400">
                                <span v-if="props.inventories && props.inventories.meta.total > 0">
                                    Showing {{ props.inventories.meta.from }} to {{ props.inventories.meta.to }} of {{
                                    props.inventories.meta.total }} products
                                </span>
                                <span v-else>No products to display</span>
                            </div>

                            <TailwindPagination :data="props.inventories" @pagination-change-page="getResults"
                                :limit="2" />
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="sticky top-0 z-10 shadow-sm">
                        <div class="space-y-3">
                            <!-- In Stock Card -->
                            <div
                                class="flex items-center rounded-lg bg-gradient-to-r from-green-50 to-green-100 p-3 shadow-md border border-green-200">
                                <div class="flex-shrink-0">
                                    <img src="/assets/images/in_stock.png" class="w-8 h-8 drop-shadow-sm"
                                        alt="In Stock" />
                                </div>
                                <div class="ml-3 flex flex-col flex-1">
                                    <span class="text-lg font-bold text-green-700">{{ inStockCount }}</span>
                                    <span class="text-xs font-medium text-green-600">In Stock</span>
                                </div>
                            </div>

                            <!-- Low Stock Card -->
                            <div
                                class="flex items-center rounded-lg bg-gradient-to-r from-orange-50 to-orange-100 p-3 shadow-md border border-orange-200">
                                <div class="flex-shrink-0">
                                    <img src="/assets/images/low_stock.png" class="w-8 h-8" alt="Low Stock" />
                                </div>
                                <div class="ml-3 flex flex-col flex-1">
                                    <span class="text-lg font-bold text-orange-700">{{ lowStockCount }}</span>
                                    <span class="text-xs font-medium text-orange-600">⚠️ Low Stock</span>
                                </div>
                            </div>

                            <!-- Reorder Level Card -->
                            <div
                                class="flex items-center rounded-lg bg-gradient-to-r from-blue-50 to-blue-100 p-3 shadow-md border border-blue-200">
                                <div class="flex-shrink-0">
                                    <img src="/assets/images/reorder_level.png" class="w-8 h-8 drop-shadow-sm"
                                        alt="Reorder Level" />
                                </div>
                                <div class="ml-3 flex flex-col flex-1">
                                    <span class="text-lg font-bold text-blue-700">{{ lowStockReorderLevelCount }}</span>
                                    <span class="text-xs font-medium text-blue-600">Low Stock + Reorder Level</span>
                                </div>
                            </div>

                            <!-- Out of Stock Card -->
                            <div
                                class="flex items-center rounded-lg bg-gradient-to-r from-red-50 to-red-100 p-3 shadow-md border border-red-200">
                                <div class="flex-shrink-0">
                                    <img src="/assets/images/out_stock.png" class="w-8 h-8 drop-shadow-sm"
                                        alt="Out of Stock" />
                                </div>
                                <div class="ml-3 flex flex-col flex-1">
                                    <span class="text-lg font-bold text-red-700">{{ outOfStockCount }}</span>
                                    <span class="text-xs font-medium text-red-600">Out of Stock</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals: Add Inventory, Upload Progress, Icon Legend -->

        <!-- Excel Upload Modal -->
        <div v-if="showUploadModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-0"
            @click="closeUploadModal">
            <div class="bg-white w-full h-full overflow-y-auto" @click.stop>
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Upload Inventory</h3>
                        <p class="text-sm text-gray-500 mt-1">Import inventory items from Excel file</p>
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
                                    Download our template to see the correct format for uploading inventory items.
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
                                    <span class="font-medium">Category</span>
                                    <span class="text-gray-400 ml-2">(required)</span>
                                </li>
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                                    <span class="font-medium">UoM</span>
                                    <span class="text-gray-400 ml-2">(required)</span>
                                </li>
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                                    <span class="font-medium">Quantity</span>
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
                            </ul>
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:bg-gray-50 transition-colors cursor-pointer"
                            @click="triggerFileInput">
                            <input type="file" ref="fileInput" class="hidden" @change="handleFileUpload"
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
                                Supports .xlsx, .xls, and .csv files (max 5MB)
                            </p>
                        </div>

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
                            <button @click.stop="removeSelectedFile"
                                class="text-red-500 hover:text-red-700 transition-colors duration-200">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Upload Progress -->
                    <div v-if="isUploading" class="mb-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Upload Progress</h4>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                :style="{ width: uploadProgress + '%' }"></div>
                        </div>
                        <p class="text-sm text-gray-600 mt-2">{{ uploadProgress }}% complete</p>
                    </div>

                    <!-- Upload Results -->
                    <div v-if="uploadResults && !isUploading" class="mb-6">
                        <div class="bg-green-50 border border-green-200 rounded-md p-4">
                            <h3 class="text-sm font-medium text-green-800">Upload Results</h3>
                            <p class="text-sm text-green-700 mt-1">{{ uploadResults.message }}</p>
                            <div v-if="uploadResults.import_id" class="mt-2 text-xs text-gray-600">
                                <p>Import ID: {{ uploadResults.import_id }}</p>
                                <p v-if="uploadResults.status">Status: {{ uploadResults.status }}</p>
                                <p v-if="uploadResults.completed_at">Completed at: {{
                                    formatDate(uploadResults.completed_at) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 bg-gray-50">
                    <button @click="closeUploadModal"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200">
                        Cancel
                    </button>
                    <button @click="uploadFile"
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 border border-transparent rounded-lg font-medium text-sm text-white hover:from-amber-600 hover:to-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition-all duration-200"
                        :disabled="!selectedFile || isUploading">
                        <svg v-if="isUploading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none"
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
                        {{ isUploading ? 'Uploading...' : 'Upload File' }}
                    </button>
                </div>
            </div>
        </div>
        <!-- Slideover for Icon Legend -->
        <transition name="slide">
            <div v-if="showLegend" class="fixed inset-0 z-50 flex justify-end">
                <div class="fixed inset-0 bg-black bg-opacity-30 transition-opacity" @click="showLegend = false"></div>
                <div
                    class="relative w-full max-w-sm bg-white shadow-xl h-full flex flex-col p-6 overflow-y-auto rounded-l-xl">
                    <button @click="showLegend = false"
                        class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <h2 class="text-lg font-bold text-blue-700 mb-6 mt-2">Icon Legend</h2>
                    <ul class="space-y-5">
                        <li class="flex items-center gap-4">
                            <img src="/assets/images/in_stock.png" class="w-10 h-10" alt="In Stock" />
                            <div>
                                <div class="font-semibold text-green-700">In Stock</div>
                                <div class="text-xs text-gray-500">Indicates items that are sufficiently stocked.</div>
                            </div>
                        </li>
                        <li class="flex items-center gap-4">
                            <img src="/assets/images/low_stock.png" class="w-10 h-10" alt="Low Stock" />
                            <div>
                                <div class="font-semibold text-orange-600">Low Stock</div>
                                <div class="text-xs text-gray-500">Indicates items that are below the reorder level.
                                </div>
                            </div>
                        </li>
                        <li class="flex items-center gap-4">
                            <img src="/assets/images/out_stock.png" class="w-10 h-10" alt="Out of Stock" />
                            <div>
                                <div class="font-semibold text-red-600">Out of Stock</div>
                                <div class="text-xs text-gray-500">Indicates items that are completely out of stock.
                                </div>
                            </div>
                        </li>
                        <li class="flex items-center gap-4">
                            <img src="/assets/images/reorder_status.png" class="w-10 h-10" alt="Reorder Status" />
                            <div>
                                <div class="font-semibold text-blue-600">Reorder Status</div>
                                <div class="text-xs text-gray-500">Indicates that a reorder is recommended for this
                                    item.</div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </transition>
    </AuthenticatedLayout>
</template>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-enter-from,
.slide-leave-to {
    transform: translateX(100%);
}

.slide-enter-to,
.slide-leave-from {
    transform: translateX(0);
}

.sortable-header {
    cursor: pointer;
    user-select: none;
    transition: background-color 0.2s ease;
}

.sortable-header:hover {
    background-color: rgba(59, 130, 246, 0.1);
}

.sort-icon {
    font-size: 0.75rem;
    margin-left: 0.25rem;
    opacity: 0.7;
}

.sort-icon.active {
    opacity: 1;
    color: #4F6FCB;
}
</style>
