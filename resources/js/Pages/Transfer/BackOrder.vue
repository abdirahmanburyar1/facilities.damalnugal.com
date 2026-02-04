<template>
    <AuthenticatedLayout title="Transfer Back Orders" description="Manage transfer back orders"
        img="/assets/images/transfer.png">
        <div class="mb-[80px]">
            <!-- Header Section -->
            <div class="flex flex-col mb-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800">Transfer Back Orders</h2>
                </div>
            </div>

            <div class="mb-[100px]">
                <div v-if="backorders.length === 0" class="text-center py-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No back orders found</h3>
                    <p class="mt-1 text-sm text-gray-500">There are currently no transfer back orders in the system.</p>
                </div>
                    
                <div v-else>
                    <table class="min-w-full border border-gray-300">
                        <thead>
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-black uppercase tracking-wider border border-black w-[8%]">
                                    Transfer ID
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-black uppercase tracking-wider border border-black w-1/4">
                                    Item
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-black uppercase tracking-wider border border-black">
                                    Warehouse
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-black uppercase tracking-wider border border-black">
                                    Item Information
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-black uppercase tracking-wider border border-black">
                                    UOM
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-black uppercase tracking-wider border border-black w-[6%]">
                                    Quantity
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-black uppercase tracking-wider border border-black w-[8%]">
                                    Type
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-black uppercase tracking-wider border border-black w-[8%]">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-black uppercase tracking-wider border border-black">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="backorder in props.backorders" :key="backorder.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-black border border-black">
                                    <Link :href="route('transfers.show', backorder.transfer_item?.transfer?.id)">
                                    {{ backorder.transfer_item?.transfer?.transferID }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-black border border-black">
                                    {{ backorder.product?.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-black border border-black">
                                    {{ backorder.transfer_item?.transfer?.to_warehouse?.name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-black border border-black">
                                    <div class="flex flex-col space-y-1">
                                        <div><span class="font-semibold">Batch:</span> {{ backorder.transfer_item?.batch_number }}</div>
                                        <div><span class="font-semibold">Expiry:</span> {{ backorder.transfer_item?.expire_date ? moment(backorder.transfer_item.expire_date).format('DD/MM/YYYY') : 'N/A' }}</div>
                                        <div><span class="font-semibold">Barcode:</span> {{ backorder.transfer_item?.barcode || '-' }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-black border border-black">
                                    {{ backorder.transfer_item?.uom || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-black border border-black">
                                    {{ backorder.quantity }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-black border border-black">
                                    {{ backorder.type }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap border border-black">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="{
                                            'bg-yellow-100 text-yellow-800': backorder.status === 'pending',
                                            'bg-green-100 text-green-800': backorder.status === 'approved',
                                            'bg-red-100 text-red-800': backorder.status === 'rejected'
                                        }">
                                        {{ backorder.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium border border-black">
                                    <button @click="receiveBackorder(backorder)" class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 mr-2">
                                        <svg class="-ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Receive
                                    </button>
                                    <button v-if="backorder.type === 'Missing'" @click="liquidateBackorder(backorder)" class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mr-2">
                                        <svg class="-ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        Liquidate
                                    </button>
                                    <button v-if="backorder.type === 'Damaged'" @click="disposeBackorder(backorder)" class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        <svg class="-ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Dispose
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                </div>
        </div>
        
        <!-- Liquidation Modal -->
        <Modal :show="showLiquidateModal" max-width="xl" @close="showLiquidateModal = false">
            <form id="liquidationForm" class="p-6 space-y-4" @submit.prevent="submitLiquidation">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Liquidate Backorder</h2>
                
                <!-- Product Info -->
                <div v-if="selectedBackorder" class="bg-gray-50 p-4 rounded-lg">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Product ID</p>
                            <p class="text-sm text-gray-900">{{ selectedBackorder.product?.productID }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Product Name</p>
                            <p class="text-sm text-gray-900">{{ selectedBackorder.product?.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Transfer ID</p>
                            <p class="text-sm text-gray-900">{{ selectedBackorder.transfer_item?.transfer?.transferID }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Status</p>
                            <p class="text-sm text-gray-900">{{ selectedBackorder.status }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                    <input 
                        type="number" 
                        id="quantity" 
                        v-model="liquidateForm.quantity"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :min="1"
                        :max="selectedBackorder?.quantity"
                        required
                    >
                </div>

                <!-- Note -->
                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700">Note</label>
                    <textarea 
                        id="note" 
                        v-model="liquidateForm.note"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        rows="3"
                        required
                    ></textarea>
                </div>

                <!-- Attachments -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Attachments (PDF files)</label>
                    <input 
                        type="file" 
                        @change="handleLiquidateFileChange"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        multiple
                        accept=".pdf"
                        required
                    >
                </div>

                <!-- Selected Files Preview -->
                <div v-if="liquidateForm.attachments.length > 0" class="mt-2">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Selected Files:</h4>
                    <ul class="space-y-2">
                        <li v-for="(file, index) in liquidateForm.attachments" :key="index" class="flex items-center justify-between text-sm text-gray-500 bg-gray-50 p-2 rounded">
                            <span>{{ file.name }}</span>
                            <button 
                                type="button"
                                @click="removeLiquidateFile(index)" 
                                class="text-red-500 hover:text-red-700"
                            >
                                Remove
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        type="button"
                        class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        @click="showLiquidateModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        :disabled="isSubmitting"
                    >
                        {{ isSubmitting ? 'Liquidating...' : 'Liquidate' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Dispose Modal -->
        <Modal :show="showDisposeModal" max-width="xl" @close="showDisposeModal = false">
            <form id="disposeForm" class="p-6 space-y-4" @submit.prevent="submitDisposal">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Dispose Backorder</h2>
                
                <!-- Product Info -->
                <div v-if="selectedBackorder" class="bg-gray-50 p-4 rounded-lg">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Product ID</p>
                            <p class="text-sm text-gray-900">{{ selectedBackorder.product?.productID }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Product Name</p>
                            <p class="text-sm text-gray-900">{{ selectedBackorder.product?.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Transfer ID</p>
                            <p class="text-sm text-gray-900">{{ selectedBackorder.transfer_item?.transfer?.transferID }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Status</p>
                            <p class="text-sm text-gray-900">{{ selectedBackorder.status }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                    <input 
                        type="number" 
                        id="quantity" 
                        v-model="disposeForm.quantity"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :min="1"
                        :max="selectedBackorder?.quantity"
                        required
                    >
                </div>

                <!-- Note -->
                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700">Note</label>
                    <textarea 
                        id="note" 
                        v-model="disposeForm.note"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        rows="3"
                        required
                    ></textarea>
                </div>

                <!-- Attachments -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Attachments (PDF files)</label>
                    <input 
                        type="file" 
                        @change="handleDisposeFileChange"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        multiple
                        accept=".pdf"
                        required
                    >
                </div>

                <!-- Selected Files Preview -->
                <div v-if="disposeForm.attachments.length > 0" class="mt-2">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Selected Files:</h4>
                    <ul class="space-y-2">
                        <li v-for="(file, index) in disposeForm.attachments" :key="index" class="flex items-center justify-between text-sm text-gray-500 bg-gray-50 p-2 rounded">
                            <span>{{ file.name }}</span>
                            <button 
                                type="button"
                                @click="removeDisposeFile(index)" 
                                class="text-red-500 hover:text-red-700"
                            >
                                Remove
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        type="button"
                        class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        @click="showDisposeModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                        :disabled="isSubmitting"
                    >
                        {{ isSubmitting ? 'Disposing...' : 'Dispose' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';
import Swal from 'sweetalert2';
import Modal from '@/Components/Modal.vue';
import moment from 'moment';

// Define props to receive data from the controller
const props = defineProps({
    backorders: Array
});

// SweetAlert Toast configuration
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
});

// Reactive state variables
const isSubmitting = ref(false);
const selectedBackorder = ref(null);
const showLiquidateModal = ref(false);
const showDisposeModal = ref(false);

// Form data for liquidation and disposal
const liquidateForm = ref({
    quantity: 0,
    note: '',
    attachments: []
});

const disposeForm = ref({
    quantity: 0,
    note: '',
    attachments: []
});

// File handling functions for liquidation
const handleLiquidateFileChange = (event) => {
    const files = Array.from(event.target.files);
    liquidateForm.value.attachments = [...liquidateForm.value.attachments, ...files];
};

const removeLiquidateFile = (index) => {
    liquidateForm.value.attachments.splice(index, 1);
};

// File handling functions for disposal
const handleDisposeFileChange = (event) => {
    const files = Array.from(event.target.files);
    disposeForm.value.attachments = [...disposeForm.value.attachments, ...files];
};

const removeDisposeFile = (index) => {
    disposeForm.value.attachments.splice(index, 1);
};



// Function to open liquidation modal
const liquidateBackorder = (backorder) => {
    selectedBackorder.value = backorder;
    liquidateForm.value.quantity = backorder.quantity;
    liquidateForm.value.note = '';
    liquidateForm.value.attachments = [];
    showLiquidateModal.value = true;
};

// Function to open disposal modal
const disposeBackorder = (backorder) => {
    selectedBackorder.value = backorder;
    disposeForm.value.quantity = backorder.quantity;
    disposeForm.value.note = '';
    disposeForm.value.attachments = [];
    showDisposeModal.value = true;
};

// Function to validate quantity
const validateQuantity = (quantity, maxQuantity) => {
    if (!quantity || quantity <= 0) {
        return 'Quantity must be greater than zero';
    }
    if (quantity > maxQuantity) {
        return `Quantity cannot exceed ${maxQuantity}`;
    }
    return null;
};

// Function to submit liquidation
const submitLiquidation = async () => {
    try {
        // Validate quantity before submission
        const quantityError = validateQuantity(liquidateForm.value.quantity, selectedBackorder.value.quantity);
        if (quantityError) {
            Toast.fire({
                icon: 'error',
                title: quantityError
            });
            return;
        }
        
        isSubmitting.value = true;
        
        // Create form data for file upload
        const formData = new FormData();
        formData.append('backorder', JSON.stringify(selectedBackorder.value));
        formData.append('quantity', liquidateForm.value.quantity);
        formData.append('note', liquidateForm.value.note);
        
        // Add attachments
        liquidateForm.value.attachments.forEach((file, index) => {
            formData.append(`attachments[${index}]`, file);
        });
        
        // Call API to liquidate backorder
        const response = await axios.post(route('transfers.liquidate'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        
        Toast.fire({
            icon: 'success',
            title: response.data.message || 'Backorder has been sent for liquidation'
        });
        
        // Close modal and reset form
        showLiquidateModal.value = false;
        liquidateForm.value = { quantity: 0, note: '', attachments: [] };
        
        // Wait a moment before redirecting to ensure toast is visible
        setTimeout(() => {
            router.get(route('transfers.back-order'));
        }, 1000);
        
    } catch (error) {
        console.error('Error liquidating backorder:', error);
        
        // Handle different error response formats
        let errorMessage = 'Failed to liquidate backorder';
        if (error.response?.data) {
            if (typeof error.response.data === 'string') {
                errorMessage = error.response.data;
            } else if (error.response.data.error) {
                errorMessage = error.response.data.error;
            } else if (error.response.data.message) {
                errorMessage = error.response.data.message;
            }
        }
        
        Toast.fire({
            icon: 'error',
            title: errorMessage
        });
    } finally {
        isSubmitting.value = false;
    }
};

// Function to submit disposal
const submitDisposal = async () => {
    try {
        // Validate quantity before submission
        const quantityError = validateQuantity(disposeForm.value.quantity, selectedBackorder.value.quantity);
        if (quantityError) {
            Toast.fire({
                icon: 'error',
                title: quantityError
            });
            return;
        }
        
        isSubmitting.value = true;
        
        // Create form data for file upload
        const formData = new FormData();
        formData.append('backorder', JSON.stringify(selectedBackorder.value));
        formData.append('quantity', disposeForm.value.quantity);
        formData.append('note', disposeForm.value.note);
        
        // Add attachments
        disposeForm.value.attachments.forEach((file, index) => {
            formData.append(`attachments[${index}]`, file);
        });
        
        // Call API to dispose backorder
        const response = await axios.post(route('transfers.dispose'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        
        Toast.fire({
            icon: 'success',
            title: response.data.message || 'Backorder has been sent for disposal'
        });
        
        // Close modal and reset form
        showDisposeModal.value = false;
        disposeForm.value = { quantity: 0, note: '', attachments: [] };
        
        // Wait a moment before redirecting to ensure toast is visible
        setTimeout(() => {
            router.get(route('transfers.back-order'));
        }, 1000);
        
    } catch (error) {
        console.error('Error disposing backorder:', error);
        
        // Handle different error response formats
        let errorMessage = 'Failed to dispose backorder';
        if (error.response?.data) {
            if (typeof error.response.data === 'string') {
                errorMessage = error.response.data;
            } else if (error.response.data.error) {
                errorMessage = error.response.data.error;
            } else if (error.response.data.message) {
                errorMessage = error.response.data.message;
            }
        }
        
        Toast.fire({
            icon: 'error',
            title: errorMessage
        });
    } finally {
        isSubmitting.value = false;
    }
};

// Function to receive backorder (for both types)
const receiveBackorder = (backorder) => {
    
    if (!backorder) {
        Toast.fire({
            icon: 'error',
            title: 'Backorder not found'
        });
        return;
    }
    
    // Show modal with quantity input
    Swal.fire({
        title: 'Receive Backorder',
        html: `
            <div class="text-left mb-4">
                <p class="mb-2"><strong>Product:</strong> ${backorder.product?.name || 'N/A'}</p>
                <p class="mb-2"><strong>Backorder Quantity:</strong> ${backorder.quantity}</p>
                <div class="mt-4">
                    <label for="received-quantity" class="block text-sm font-medium text-gray-700 mb-1">Received Quantity:</label>
                    <input type="number" id="received-quantity" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md" 
                        min="1" max="${backorder.quantity}" value="${backorder.quantity}"
                        oninput="if(this.value > ${backorder.quantity}) this.value = ${backorder.quantity};">
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Receive',
        preConfirm: () => {
            const receivedQuantity = document.getElementById('received-quantity').value;
            if (!receivedQuantity || receivedQuantity <= 0) {
                Swal.showValidationMessage('Please enter a valid quantity');
                return false;
            }
            if (receivedQuantity > backorder.quantity) {
                Swal.showValidationMessage(`Received quantity cannot exceed backorder quantity (${backorder.quantity})`);
                return false;
            }
            return receivedQuantity;
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            const receivedQuantity = result.value;
            
            // Show loading indicator
            Swal.fire({
                title: 'Processing...',
                html: 'Receiving backorder items, please wait.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Call API to receive backorder with quantity
            try {
                const response = await axios.post(route('transfers.receiveBackOrder'), {
                    backorder: backorder,
                    quantity: receivedQuantity
                });
                
                // Close loading indicator
                Swal.close();
                
                Toast.fire({
                    icon: 'success',
                    title: response.data.message || 'Backorder has been received successfully'
                });
                
                // Wait a moment before redirecting to ensure toast is visible
                setTimeout(() => {
                    router.get(route('transfers.back-order'));
                }, 1000);
                
            } catch (error) {
                console.error('Error receiving backorder:', error);
                
                // Close loading indicator
                Swal.close();
                
                // Handle different error response formats
                let errorMessage = 'Failed to receive backorder';
                if (error.response?.data) {
                    if (typeof error.response.data === 'string') {
                        errorMessage = error.response.data;
                    } else if (error.response.data.error) {
                        errorMessage = error.response.data.error;
                    } else if (error.response.data.message) {
                        errorMessage = error.response.data.message;
                    }
                }
                
                Toast.fire({
                    icon: 'error',
                    title: errorMessage
                });
            }
        }
    });
};
</script>
