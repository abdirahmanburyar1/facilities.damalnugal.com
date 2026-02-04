<template>
    <Head title="View Supply Details" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Supply Details</h2>
                <div class="flex space-x-2">
                    <Link :href="route('supplies.edit', supply.id)" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Edit
                    </Link>
                    <Link :href="route('supplies.index')" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Back to Supplies
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Supply Information -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Supply Information</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Product</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ supply.product.name }}</p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Warehouse</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ supply.warehouse.name }}</p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Quantity</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ supply.quantity }}</p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Unit Price</p>
                                        <p class="mt-1 text-sm text-gray-900">${{ supply.unit_price }}</p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Total Price</p>
                                        <p class="mt-1 text-sm text-gray-900">${{ supply.total_price }}</p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Supply Date</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ formatDate(supply.supply_date) }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Supplier Information -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Supplier Information</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Supplier Name</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ supply.supplier ? supply.supplier.name : '—' }}</p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Contact Person</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ supply.supplier ? supply.supplier.contact_person : '—' }}</p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Email</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ supply.supplier ? supply.supplier.email : '—' }}</p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Phone</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ supply.supplier ? supply.supplier.phone : '—' }}</p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Invoice Number</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ supply.invoice_number || '—' }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Batch Information -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Batch Information</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Batch Number</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ supply.batch_number || '—' }}</p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Manufacturing Date</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ formatDate(supply.manufacturing_date) || '—' }}</p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Expiry Date</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ formatDate(supply.expiry_date) || '—' }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Notes -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Notes</h3>
                                
                                <p class="text-sm text-gray-900">{{ supply.notes || 'No notes available' }}</p>
                            </div>
                        </div>
                        
                        <!-- Timestamps -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex justify-between text-sm text-gray-500">
                                <p>Created: {{ formatDateTime(supply.created_at) }}</p>
                                <p>Last Updated: {{ formatDateTime(supply.updated_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    supply: Object,
});

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    return date.toLocaleDateString();
};

// Format date and time for display
const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return null;
    const date = new Date(dateTimeString);
    return date.toLocaleString();
};
</script>
