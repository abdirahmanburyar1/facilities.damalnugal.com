<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";
import "@/Components/multiselect.css";
import axios from "axios";
import { useToast } from "vue-toastification";
import Swal from "sweetalert2";
import moment from "moment";

const toast = useToast();

const props = defineProps({
    inventory: Object,
    warehouses: {
        type: Array,
        default: () => [],
    },
    facilities: {
        type: Array,
        default: () => [],
    },
    transferID: {
        type: String,
        required: true,
    },
    facilityID: {
        type: Number,
        required: true,
    },
    reasons: {
        type: Array,
        required: true,
    },
});

const destinationType = ref("warehouse");
const loading = ref(false);
const selectedDestination = ref(null);
const selectedInventory = ref(null);
const filteredInventories = ref([]);
const availableInventories = ref([]);
const searchQuery = ref("");
const loadingInventories = ref(false);

const form = ref({
    source_type: "facility", // Always facility for facilities
    source_id: props.facilityID, // Always the current facility
    destination_type: "warehouse",
    destination_id: null,
    transfer_date: moment().format("YYYY-MM-DD"),
    transferID: props.transferID,
    items: [
        {
            id: null,
            product_id: "",
            product: null,
            quantity: 0,
            available_quantity: 0,
            details: [],
        },
    ],
});

const errors = ref({});

const destinationOptions = computed(() => {
    return destinationType.value === "warehouse"
        ? props.warehouses
        : props.facilities;
});

const handleDestinationSelect = (selected) => {
    form.value.destination_id = selected ? selected.id : null;
};

const fetchInventories = async () => {
    // Set loading state
    loadingInventories.value = true;
    filteredInventories.value = [];
    searchQuery.value = "";

    // Show Swal loading counter
    let timerInterval;
    const swalLoading = Swal.fire({
        title: "Loading Inventory",
        html: "Fetching inventory items... <b></b>",
        timer: 30000, // 30 seconds max timeout
        timerProgressBar: true,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
            const timer = Swal.getPopup().querySelector("b");
            timerInterval = setInterval(() => {
                timer.textContent = `${Math.ceil(Swal.getTimerLeft() / 1000)}s`;
            }, 100);
        },
        willClose: () => {
            clearInterval(timerInterval);
        },
    });

    try {
        // Use GET request to fetch available inventories
        const response = await axios.get(route("transfers.getInventories"), {
            params: {
                source_type: form.value.source_type,
                source_id: form.value.source_id,
            },
        });

        console.log("[SUCCESS] Fetch inventories response:", response.data);
        swalLoading.close();
        availableInventories.value = response.data;
        filteredInventories.value = availableInventories.value;
        loadingInventories.value = false;
    } catch (error) {
        console.error("[ERROR] Fetch inventories failed:", error);
        console.error("[ERROR] Full error object:", error.response);

        // Close loading dialog first
        swalLoading.close();

        // Reset states
        loadingInventories.value = false;
        availableInventories.value = [];
        filteredInventories.value = [];

        // Add small delay before showing error dialog to avoid timing conflicts
        setTimeout(() => {
            let errorMessage = "Failed to fetch inventories";

            if (error.response) {
                console.log(
                    "[DEBUG] Error response status:",
                    error.response.status
                );
                console.log(
                    "[DEBUG] Error response data:",
                    error.response.data
                );

                if (typeof error.response.data === "string") {
                    errorMessage = error.response.data;
                } else if (error.response.data && error.response.data.message) {
                    errorMessage = error.response.data.message;
                } else if (error.response.data && error.response.data.error) {
                    errorMessage = error.response.data.error;
                } else {
                    errorMessage = `Server error (${error.response.status})`;
                }
            } else if (error.message) {
                errorMessage = error.message;
            }

            console.log("[DEBUG] Final error message:", errorMessage);

            Swal.fire({
                title: "Error!",
                text: errorMessage,
                icon: "error",
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 5000,
            });
        }, 100); // 100ms delay
    }
};

const validateForm = () => {
    errors.value = {};
    let isValid = true;

    // Validate destination
    if (!form.value.destination_id) {
        errors.value.destination_id = "Please select a destination.";
        isValid = false;
    }

    // Check if source and destination are the same
    if (
        form.value.source_id === form.value.destination_id &&
        form.value.source_type === form.value.destination_type
    ) {
        errors.value.destination_id =
            "Source and destination cannot be the same.";
        isValid = false;
    }

    // Validate that all items are properly filled
    let hasValidItems = false;

    form.value.items.forEach((item, index) => {
        // Check if inventory item is selected
        if (!item.inventory_id) {
            errors.value[`item_${index}_inventory`] =
                "Please select an inventory item.";
            isValid = false;
        }

        // Check if quantity is valid (must be at least 1)
        if (item.inventory_id && (!item.quantity || item.quantity < 1)) {
            errors.value[`item_${index}_quantity`] =
                "Quantity must be at least 1.";
            isValid = false;
        }

        // Check if quantity exceeds available quantity
        if (item.inventory_id && item.quantity > item.available_quantity) {
            errors.value[
                `item_${index}_quantity`
            ] = `Maximum available quantity is ${item.available_quantity}.`;
            isValid = false;
        }

        // Track if we have at least one valid item
        if (
            item.inventory_id &&
            item.quantity >= 1 &&
            item.quantity <= item.available_quantity
        ) {
            hasValidItems = true;
        }
    });

    // Ensure at least one valid item exists
    if (!hasValidItems) {
        errors.value.items =
            "At least one item must be selected with a valid quantity.";
        isValid = false;
    }

    return isValid;
};

// Watch for changes in destination type and update form
watch(destinationType, (newValue) => {
    form.value.destination_type = newValue;
    form.value.destination_id = null;
    selectedDestination.value = null;
});

// Load inventories on component mount
fetchInventories();

const submit = async () => {
    loading.value = true;

    // Validate that at least one item has quantity to transfer
    const hasItemsToTransfer = form.value.items.some(item => 
        item.details.some(detail => 
            parseFloat(detail.quantity_to_transfer) > 0
        )
    );

    if (!hasItemsToTransfer) {
        loading.value = false;
        Swal.fire({
            title: "No Items to Transfer",
            text: "Please specify quantities to transfer for at least one item.",
            icon: "warning",
            confirmButtonColor: "#4F46E5",
        });
        return;
    }

    // // Filter out items with no quantity to transfer
    const filteredItems = form.value.items.filter(item => 
        item.product_id && item.details.some(detail => 
            parseFloat(detail.quantity_to_transfer) > 0
        )
    );

    const submitData = {
        ...form.value,
        items: filteredItems
    };

    console.log(submitData);
    
    await axios
        .post(route("transfers.store"), submitData)
        .then((response) => {
            loading.value = false;
            Swal.fire({
                title: "Success!",
                text: response.data,
                icon: "success",
                confirmButtonColor: "#4F46E5",
            }).then(() => {
                router.get(route("transfers.index"));
            });
        })
        .catch((error) => {
            console.error(error.response);
            loading.value = false;
            Swal.fire({
                title: "Error!",
                text: error.response?.data || "Failed to create transfer",
                icon: "error",
                confirmButtonColor: "#4F46E5",
            });
        });
};

const isLoading = ref([]);
async function handleProductSelect(index, selected) {
    isLoading.value[index] = true;
    const item = form.value.items[index];
    item.details = [];
    if (selected) {
        await axios
            .post(route("transfers.inventory"), {
                product_id: selected.id,
                source_type: form.value.source_type,
                source_id: form.value.source_id,
            })
            .then((response) => {
                isLoading.value[index] = false;
                console.log(response.data);

                // Initialize quantity_to_transfer and transfer_reason for each detail item
                item.details = response.data.map(detail => ({
                    ...detail,
                    quantity_to_transfer: 0,
                    transfer_reason: ''
                }));
                
                item.available_quantity = response.data?.reduce(
                    (sum, detail) => sum + detail.quantity,
                    0
                );
                item.product = selected;
                item.product_id = selected.id;
                item.quantity = 0; // Reset main quantity
                addNewItem();
            })
            .catch((error) => {
                isLoading.value[index] = false;
                console.log(error);

                // Clear product fields on error
                item.product_id = null;
                item.product = null;
                item.details = [];
                item.available_quantity = 0;

                Swal.fire({
                    title: "Error!",
                    text: error.response.data,
                    icon: "error",
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 5000,
                });
            });
    }
}

// Function to update main item quantity based on detail quantities
function updateItemQuantity(index) {
    const item = form.value.items[index];
    const totalQuantity = item.details.reduce((sum, detail) => {
        return sum + (parseFloat(detail.quantity_to_transfer) || 0);
    }, 0);
    
    item.quantity = totalQuantity;
    
    // Clear any existing quantity errors
    if (errors.value[`item_${index}_quantity`]) {
        errors.value[`item_${index}_quantity`] = null;
    }
}

function addNewItem() {
    // Check if there's already an empty row (no product selected)
    const hasEmptyRow = form.value.items.some(item => 
        !item.product_id || item.product_id === null || item.product_id === ''
    );
    
    // Only add new item if all existing items have products selected
    if (!hasEmptyRow) {
        form.value.items.push({
            id: null,
            product_id: null,
            product: null,
            available_quantity: 0,
            quantity: 0,
            details: [],
        });
    } 
}

function removeItem(index) {
    // Check if we have more than one item before removing
    if (form.value.items.length > 1) {
        form.value.items.splice(index, 1);
    }
}

function checkQuantity(index) {
    const item = form.value.items[index];

    // Ensure quantity is at least 1
    if (item.quantity < 1) {
        item.quantity = 1;
        toast.info("Minimum quantity is 1");
    }

    // Ensure quantity doesn't exceed available quantity
    if (item.quantity > item.available_quantity) {
        // Reset to available quantity if exceeded
        item.quantity = item.available_quantity;
        toast.warning(
            `Quantity reset to maximum available (${item.available_quantity})`
        );
    }
}

function formatDate(date) {
    return moment(date).format("DD/MM/YYYY");
}

// Function to check if date is expiring soon (within 6 months)
function isExpiringSoon(expiryDate) {
    if (!expiryDate) return false;
    const today = moment();
    const expiry = moment(expiryDate);
    const monthsDiff = expiry.diff(today, 'months');
    return monthsDiff <= 6 && monthsDiff >= 0;
}
</script>

<template>
    <AuthenticatedLayout
        title="Optimize Your Transfers"
        description="Moving Supplies, Bridging needs"
        img="/assets/images/transfer.png"
    >
        <div class="bg-gradient-to-br to-blue-50 mb-[80px]">
            <!-- Header Section -->
            <div class="bg-white mb-8">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Create Transfer</h1>
                            <p class="text-gray-600 mt-1">Transfer inventory items between locations</p>
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-4 py-2 rounded-xl">
                        <div class="text-sm font-medium">Transfer ID</div>
                        <div class="text-lg font-bold">{{ props.transferID }}</div>
                    </div>
                </div>
                
                <!-- Info Alert -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 mb-6">
                    <div class="flex items-start space-x-3">
                        <div class="w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-blue-800 font-medium">Transfer Instructions</p>
                            <p class="text-blue-700 text-sm mt-1">
                                Select destination location, then choose inventory items to transfer. 
                                Specify the exact quantity for each batch and provide a transfer reason.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Form Section -->
            <div class="bg-white rounded-2xl border border-slate-200 p-8">
                <form @submit.prevent="submit" class="space-y-8">
                    <!-- Transfer Date -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-1">
                            <label for="transfer_date" class="block text-sm font-semibold text-gray-700 mb-2">
                                Transfer Date
                            </label>
                            <input
                                type="date"
                                v-model="form.transfer_date"
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                            />
                        </div>
                    </div>

                    <!-- Destination Section -->
                    <div class="space-y-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h14m-6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Transfer To</h3>
                        </div>
                        
                        <!-- Destination Type Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Destination Type</label>
                            <div class="flex space-x-4">
                                <label class="flex items-center px-4 py-2 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors duration-200"
                                       :class="destinationType === 'warehouse' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-300'">
                                    <input
                                        type="radio"
                                        v-model="destinationType"
                                        value="warehouse"
                                        class="sr-only"
                                    />
                                    <div class="w-4 h-4 rounded-full border-2 mr-2 flex items-center justify-center"
                                         :class="destinationType === 'warehouse' ? 'border-blue-500' : 'border-gray-400'">
                                        <div v-if="destinationType === 'warehouse'" class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    </div>
                                    <span class="font-medium">Warehouse</span>
                                </label>
                                <label class="flex items-center px-4 py-2 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors duration-200"
                                       :class="destinationType === 'facility' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-300'">
                                    <input
                                        type="radio"
                                        v-model="destinationType"
                                        value="facility"
                                        class="sr-only"
                                    />
                                    <div class="w-4 h-4 rounded-full border-2 mr-2 flex items-center justify-center"
                                         :class="destinationType === 'facility' ? 'border-blue-500' : 'border-gray-400'">
                                        <div v-if="destinationType === 'facility'" class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    </div>
                                    <span class="font-medium">Facility</span>
                                </label>
                            </div>
                        </div>

                        <!-- Destination Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Select Destination {{ destinationType === 'warehouse' ? 'Warehouse' : 'Facility' }}
                            </label>
                            <Multiselect
                                v-model="selectedDestination"
                                :options="destinationOptions"
                                :searchable="true"
                                :close-on-select="true"
                                :show-labels="false"
                                :allow-empty="true"
                                :placeholder="`Select destination ${destinationType === 'warehouse' ? 'warehouse' : 'facility'}`"
                                track-by="id"
                                label="name"
                                @select="handleDestinationSelect"
                                required
                                :class="{ 'border-red-500': errors.destination_id }"
                                @open="errors.destination_id = null"
                            >
                                <template v-slot:option="{ option }">
                                    <span>{{ option.name }}</span>
                                </template>
                            </Multiselect>
                            <InputError :message="errors.destination_id" class="mt-2" />
                        </div>
                    </div>

                    <!-- Items Table Section -->
                    <div class="mb-4">
                        <table class="w-full text-sm text-left table-sm rounded-t-lg">
                            <thead>
                                <tr style="background-color: #F4F7FB;">
                                    <th class="min-w-[120px] px-3 py-2 text-xs font-bold rounded-tl-lg" style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">
                                        Item Name
                                    </th>
                                    <th class="px-3 py-2 text-xs font-bold" style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">
                                        Category
                                    </th>
                                    <th class="px-3 py-2 text-xs font-bold" style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">
                                        UoM
                                    </th>
                                    <th class="px-3 py-2 text-xs font-bold text-center" style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">
                                        Total Quantity on Hand Per Unit
                                    </th>
                                    <th class="px-3 py-2 text-xs font-bold text-center" style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" colspan="4">
                                        Item details
                                    </th>
                                    
                                    <th class="min-w-[150px] px-3 py-2 text-xs font-bold" style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">
                                        Reasons for Transfers
                                    </th>
                                    <th class="min-w-[110px] px-3 py-2 text-xs font-bold" style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">
                                        Quantity to be transferred
                                    </th>
                                    <th class="px-3 py-2 text-xs font-bold" style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">
                                        Total Quantity to be transferred
                                    </th>
                                    <th class="px-3 py-2 text-xs font-bold rounded-tr-lg" style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">
                                        Action
                                    </th>
                                </tr>
                                <tr style="background-color: #F4F7FB;">
                                    <th class="px-2 py-1 text-xs font-bold border border-[#B7C6E6] text-center" style="color: #4F6FCB;">
                                        QTY
                                    </th>
                                    <th class="px-2 py-1 text-xs font-bold border border-[#B7C6E6] text-center" style="color: #4F6FCB;">
                                        Batch Number
                                    </th>
                                    <th class="px-2 py-1 text-xs font-bold border border-[#B7C6E6] text-center" style="color: #4F6FCB;">
                                        Expiry Date
                                    </th>
                                    <th class="px-2 py-1 text-xs font-bold border border-[#B7C6E6] text-center" style="color: #4F6FCB;">
                                        Location
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <template v-for="(item, index) in form.items" :key="index">
                                    <!-- Show details if they exist, otherwise show one row with main item data -->
                                    <tr v-for="(detail, detailIndex) in (item.details?.length > 0 ? item.details : [{}])"
                                        :key="`${index}-${detail.id || detailIndex}`"
                                        class="hover:bg-gray-50 transition-colors duration-150 border-b"
                                        style="border-bottom: 1px solid #B7C6E6;">
                                        
                                        <!-- Item Name - only on first row for this item -->
                                        <td v-if="detailIndex === 0" :rowspan="Math.max(item.details?.length || 1, 1)"
                                            class="min-w-[200px] px-3 py-2 text-xs font-medium text-gray-800 align-top">
                                            <div class="w-full">
                                                <Multiselect
                                                    v-model="item.product"
                                                    :value="item.product_id"
                                                    :options="availableInventories"
                                                    placeholder="Search for an item..."
                                                    required
                                                    track-by="id"
                                                    label="name"
                                                    :searchable="true"
                                                    :allow-empty="true"
                                                    :loading="isLoading[index]"
                                                    @select="handleProductSelect(index, $event)"
                                                />
                                            </div>
                                        </td>

                                        <!-- Category - only on first row for this item -->
                                        <td v-if="detailIndex === 0" :rowspan="Math.max(item.details?.length || 1, 1)"
                                            class="px-3 py-2 text-xs text-gray-700 align-top">
                                            {{ item.product?.category?.name || '' }}
                                        </td>

                                        <!-- UoM - only on first row for this item -->
                                        <td v-if="detailIndex === 0" :rowspan="Math.max(item.details?.length || 1, 1)"
                                            class="px-3 py-2 text-xs text-gray-700 align-top">
                                            {{ item.details?.[0]?.uom || '' }}
                                        </td>

                                        <!-- Total Quantity on Hand Per Unit - only on first row for this item -->
                                        <td v-if="detailIndex === 0" :rowspan="Math.max(item.details?.length || 1, 1)"
                                            class="px-3 py-2 text-xs text-gray-800 align-top">
                                            {{ item.available_quantity || 0 }}
                                        </td>

                                        <!-- Item Details Columns -->
                                        <!-- Quantity -->
                                        <td class="px-2 py-1 text-xs text-center" :class="detail.expiry_date && isExpiringSoon(detail.expiry_date) ? 'text-red-600 font-medium' : 'text-gray-900'">
                                            {{ detail.quantity || '' }}
                                        </td>

                                        <!-- Batch Number -->
                                        <td class="px-2 py-1 text-xs text-center" :class="detail.expiry_date && isExpiringSoon(detail.expiry_date) ? 'text-red-600 font-medium' : 'text-gray-900'">
                                            {{ detail.batch_number || '' }}
                                        </td>

                                        <!-- Expiry Date -->
                                        <td class="px-2 py-1 text-xs text-center" :class="detail.expiry_date && isExpiringSoon(detail.expiry_date) ? 'text-red-600 font-medium' : 'text-gray-900'">
                                            {{ detail.expiry_date ? formatDate(detail.expiry_date) : '' }}
                                        </td>

                                        <!-- Location -->
                                        <td class="px-2 py-1 text-xs text-center">
                                            {{ detail.location || '' }}
                                        </td>

                                        <!-- Reasons for Transfers - per detail -->
                                        <td class="px-2 py-1 text-xs text-center">
                                        <Multiselect
                                            v-if="item.product && detail.quantity"
                                            v-model="detail.transfer_reason"
                                            :options="props.reasons"
                                            :searchable="true"
                                            :close-on-select="true"
                                            :show-labels="false"
                                        />
                                        </td>

                                        <!-- Quantity to be transferred - per detail -->
                                        <td class="px-2 py-1 text-xs text-center">
                                            <input
                                                v-if="item.product && detail.quantity"
                                                type="number"
                                                v-model.number="detail.quantity_to_transfer"
                                                :max="detail.quantity"
                                                min="0"
                                                class="w-full text-xs border rounded px-2 py-1 text-center"
                                                placeholder="0"
                                                @input="updateItemQuantity(index)"
                                            />
                                        </td>

                                        <!-- Total Quantity to be transferred - only on first row for this item -->
                                        <td v-if="detailIndex === 0" :rowspan="Math.max(item.details?.length || 1, 1)"
                                            class="px-3 py-2 text-xs text-gray-800 align-top">
                                            <div class="text-sm font-medium text-blue-600" v-if="item.product">
                                                {{ item.quantity || 0 }}
                                            </div>
                                        </td>

                                        <!-- Action - only on first row for this item -->
                                        <td v-if="detailIndex === 0" :rowspan="Math.max(item.details?.length || 1, 1)"
                                            class="px-3 py-4 whitespace-nowrap text-xs font-medium align-top">
                                            <button
                                                type="button"
                                                @click="removeItem(index)"
                                                class="text-red-600 hover:text-red-800"
                                                :disabled="form.items.length <= 1"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between pt-8 border-t border-gray-200">
                        <button
                            type="button"
                            @click="addNewItem"
                            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Another Item
                        </button>
                        <div class="flex items-center space-x-4">
                            <SecondaryButton
                                :href="route('transfers.index')"
                                as="a"
                                :disabled="loading"
                                class="px-8 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-all duration-200"
                                :class="{
                                    'opacity-50 cursor-not-allowed': loading,
                                }"
                            >
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton :disabled="loading" class="relative px-8 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                                <span v-if="loading" class="absolute left-3">
                                    <svg
                                        class="animate-spin h-5 w-5 text-white"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        ></circle>
                                        <path
                                            class="overflow-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        ></path>
                                    </svg>
                                </span>
                                <span :class="{ 'pl-7': loading }">{{
                                    loading ? "Processing..." : "Create Transfer"
                                }}</span>
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
