<template>
    <AuthenticatedLayout title="Transfer Liquidates" description="Manage transfer liquidations"
        img="/assets/images/transfer.png">
        <div class="mb-[80px]">
            <!-- Header Section -->
            <div class="flex flex-col mb-6 space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800">Transfer Liquidates</h2>
                </div>
            </div>

            <!-- Main Content -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="p-6">
                    <div class="text-center py-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Transfer Liquidates</h3>
                        <p class="mt-1 text-sm text-gray-500">Manage items that need to be liquidated during transfer process.</p>
                    </div>
                    
                    <div class="mt-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Transfer ID
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Item
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        From Warehouse
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        To Warehouse
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Quantity
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Reason
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <!-- This will be populated with data from the controller -->
                                <tr class="text-center">
                                    <td colspan="8" class="px-6 py-4 text-sm text-gray-500">
                                        No liquidation records found
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

// Define props to receive data from the controller
const props = defineProps({
    liquidates: {
        type: Array,
        default: () => []
    }
});

// Toast notification configuration
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});

// Function to handle liquidation approval
const approveLiquidation = (id) => {
    Swal.fire({
        title: 'Are you sure?',
        text: "You are about to approve this liquidation request",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, approve it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Call API to approve liquidation
            axios.post(route('transfers.liquidate.approve', id))
                .then(response => {
                    Toast.fire({
                        icon: 'success',
                        title: 'Liquidation approved successfully'
                    });
                    router.reload();
                })
                .catch(error => {
                    Toast.fire({
                        icon: 'error',
                        title: error.response?.data?.message || 'Failed to approve liquidation'
                    });
                });
        }
    });
};

// Function to handle liquidation rejection
const rejectLiquidation = (id) => {
    Swal.fire({
        title: 'Are you sure?',
        text: "You are about to reject this liquidation request",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, reject it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Call API to reject liquidation
            axios.post(route('transfers.liquidate.reject', id))
                .then(response => {
                    Toast.fire({
                        icon: 'success',
                        title: 'Liquidation rejected successfully'
                    });
                    router.reload();
                })
                .catch(error => {
                    Toast.fire({
                        icon: 'error',
                        title: error.response?.data?.message || 'Failed to reject liquidation'
                    });
                });
        }
    });
};
</script>
