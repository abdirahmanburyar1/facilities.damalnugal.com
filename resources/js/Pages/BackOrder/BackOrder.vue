<template>
    <AuthenticatedLayout :title="'Back Order'" description="Track Your Back Orders" img="/assets/images/orders.png">
        <div class="p-6 text-gray-900">
            <div class="mb-4 w-[400px]">
                <label for="source" class="block text-sm font-medium text-gray-700">Select Source</label>
                <Multiselect v-model="selectedSource" :options="sourceOptions" :searchable="true" :create-option="false"
                    class="mt-1" placeholder="Select Order or Transfer" label="display_name" track-by="id"
                    @select="handleSourceChange" />
            </div>

            <div class="mt-6" v-if="selectedSource">
                <h3 class="text-lg font-medium text-gray-900">Back Order Items</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Process each item: <strong>Receive</strong> (all statuses), <strong>Liquidate</strong> (Missing only), or <strong>Dispose</strong> (Damaged / Lost / Expired / Low quality).
                </p>
                
                <!-- Back Order Information Card -->
                <div class="mt-4 bg-white border border-gray-200 rounded-lg shadow-sm p-6" v-if="backOrderInfo">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Back Order Number</p>
                            <p class="text-lg font-semibold text-gray-900">{{ backOrderInfo.back_order_number }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Back Order Date</p>
                            <p class="text-lg font-semibold text-gray-900">{{ moment(backOrderInfo.back_order_date).format('DD/MM/YYYY') }}</p>
                        </div>
                    </div>
                    <!-- Parent-level Attachments -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700">Back Order Attachments (PDF files)</label>
                        <input type="file" multiple accept=".pdf" @change="handleParentAttachments" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                        <div v-if="parentAttachments.length > 0" class="mt-2">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Selected Files:</h4>
                            <ul class="space-y-2">
                                <li v-for="(file, index) in parentAttachments" :key="index" class="flex items-center justify-between text-sm text-gray-500 bg-gray-50 p-2 rounded">
                                    <span>{{ file.name }}</span>
                                    <button type="button" @click="removeParentAttachment(index)" class="text-red-500 hover:text-red-700">Remove</button>
                                </li>
                            </ul>
                            <button type="button" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded flex items-center justify-center" @click="uploadParentAttachments" :disabled="isUploading">
                                <span v-if="isUploading" class="loader mr-2"></span>
                                <span>{{ isUploading ? 'Uploading...' : 'Upload' }}</span>
                            </button>
                        </div>
                        <div v-if="backOrderInfo.attach_documents && backOrderInfo.attach_documents.length" class="mt-2">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Uploaded Files:</h4>
                            <ul class="space-y-2">
                                <li v-for="(doc, i) in backOrderInfo.attach_documents" :key="i" class="flex items-center justify-between text-sm text-gray-500 bg-gray-50 p-2 rounded">
                                    <a :href="doc.path" target="_blank" class="text-blue-600 underline">{{ doc.name }}</a>
                                    <button type="button" @click="deleteParentAttachment(doc.path)" class="text-red-500 hover:text-red-700">Delete</button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-col">
                    <div class="overflow-auto w-full">
                        <table class="w-full overflow-hidden text-sm text-left table-sm rounded-t-lg">
                            <thead>
                                <tr style="background-color: #F4F7FB;">
                                    <th class="px-3 py-2 text-xs font-bold rounded-tl-lg" style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">Item ID</th>
                                    <th class="px-3 py-2 text-xs font-bold" style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">Item Name</th>
                                    <th class="px-3 py-2 text-xs font-bold" style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">Source</th>
                                    <th class="px-3 py-2 text-xs font-bold" style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">Date</th>
                                    <th class="px-3 py-2 text-xs font-bold" style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">Quantity</th>
                                    <th class="px-3 py-2 text-xs font-bold" style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">Status</th>
                                    <th class="px-3 py-2 text-xs font-bold" style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;" rowspan="2">Actions</th>
                                </tr>
                            </thead>
                                    <tbody>
                                        <template v-if="isLoading">
                                            <tr v-for="i in 3" :key="i">
                                                <td v-for="j in 7" :key="j" class="px-3 py-2">
                                                    <div class="animate-pulse h-4 bg-gray-200 rounded"></div>
                                                </td>
                                            </tr>
                                        </template>
                                        <template v-else>
                                            <template v-for="item in groupedItems" :key="item.id">
                                                <tr v-for="(row, index) in item.rows" :key="index"
                                                    class="hover:bg-gray-50 transition-colors duration-150 border-b" style="border-bottom: 1px solid #B7C6E6;">
                                                    <td class="px-3 py-2 text-xs font-medium text-gray-800 align-middle"
                                                        v-if="index === 0" :rowspan="item.rows.length">
                                                        {{ item.product.productID }}
                                                    </td>
                                                    <td class="px-3 py-2 text-xs text-gray-700 align-middle"
                                                        v-if="index === 0" :rowspan="item.rows.length">
                                                        {{ item.product.name }}
                                                    </td>
                                                    <td class="px-3 py-2 text-xs text-gray-700 align-middle"
                                                        v-if="index === 0" :rowspan="item.rows.length">
                                                        {{ getSourceDisplayName(item) }}
                                                    </td>
                                                    <td class="px-3 py-2 text-xs text-gray-700 align-middle"
                                                        v-if="index === 0" :rowspan="item.rows.length">
                                                        {{ moment(item.created_at).format('DD/MM/YYYY') }}
                                                    </td>
                                                    <td class="px-3 py-2 text-xs text-gray-900 text-center align-middle">
                                                        {{ row.quantity }}
                                                    </td>
                                                    <td class="px-3 py-2 text-xs text-center align-middle">
                                                        <span v-if="row.status === 'Missing'"
                                                            class="text-yellow-600 font-medium">
                                                            Missing
                                                        </span>
                                                        <span v-else-if="row.status === 'Damaged'"
                                                            class="text-red-600 font-medium">
                                                            Damaged
                                                        </span>
                                                        <span v-else-if="row.status === 'Lost'"
                                                            class="text-gray-600 font-medium">
                                                            Lost
                                                        </span>
                                                        <span v-else-if="row.status === 'Expired'"
                                                            class="text-orange-600 font-medium">
                                                            Expired
                                                        </span>
                                                        <span v-else-if="row.status === 'Low quality'"
                                                            class="text-purple-600 font-medium">
                                                            Low quality
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-2 text-xs text-center align-middle">
                                                        <div class="flex items-center justify-center space-x-2">
                                                            <!-- Receive action - available for all statuses -->
                                                            <button
                                                                @click="handleAction('Receive', { ...item, id: row.id, status: row.status, quantity: row.quantity })"
                                                                class="px-2 py-1 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700 transition-colors duration-150"
                                                                :disabled="isLoading">
                                                                Receive
                                                            </button>
                                                            
                                                            <!-- Liquidate action - only for Missing status -->
                                                            <button 
                                                                v-if="row.status === 'Missing'"
                                                                @click="handleAction('Liquidate', { ...item, id: row.id, status: row.status, quantity: row.quantity })"
                                                                class="px-2 py-1 text-xs font-medium text-white bg-yellow-500 rounded hover:bg-yellow-600 transition-colors duration-150"
                                                                :disabled="isLoading">
                                                                Liquidate
                                                            </button>
                                                            
                                                            <!-- Dispose action - for all statuses except Missing -->
                                                            <button 
                                                                v-if="row.status !== 'Missing'"
                                                                @click="handleAction('Dispose', { ...item, id: row.id, status: row.status, quantity: row.quantity })"
                                                                class="px-2 py-1 text-xs font-medium text-white bg-red-600 rounded hover:bg-red-700 transition-colors duration-150"
                                                                :disabled="isLoading">
                                                                Dispose
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                            <tr v-if="items.length === 0">
                                                <td colspan="7" class="text-center py-8 text-gray-500 bg-gray-50">
                                                    <div class="flex flex-col items-center justify-center gap-2">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 118 0v2m-4 4a4 4 0 01-4-4H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-2a4 4 0 01-4 4z" />
                                                        </svg>
                                                        <span>No back order items found.</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
            </div>
        </div>

        <!-- Liquidation Modal -->
        <Modal :show="showLiquidateModal" max-width="xl" @close="showLiquidateModal = false">
            <form id="liquidationForm" class="p-6 space-y-4" @submit.prevent="submitLiquidation">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Liquidate Item</h2>

                <!-- Product Info -->
                <div v-if="selectedItem" class="bg-gray-50 p-4 rounded-lg">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Product ID</p>
                            <p class="text-sm text-gray-900">{{ selectedItem.product.productID }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Product Name</p>
                            <p class="text-sm text-gray-900">{{ selectedItem.product.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Source</p>
                            <p class="text-sm text-gray-900">{{ getSourceDisplayName(selectedItem) }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Status</p>
                            <p class="text-sm text-gray-900">{{ selectedItem.status }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                    <input type="number" id="quantity" v-model="liquidateForm.quantity" readonly
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :min="1" :max="selectedItem?.quantity" required>
                </div>

                <!-- Note -->
                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700">Note</label>
                    <textarea id="note" v-model="liquidateForm.note"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        rows="3" required></textarea>
                </div>

                <!-- Attachments -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Attachments (Optional)</label>
                    <input type="file" ref="attachments" @change="(e) => handleFileChange('liquidate', e)"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        multiple>
                </div>

                <!-- Selected Files Preview -->
                <div v-if="liquidateForm.attachments.length > 0" class="mt-2">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Selected Files:</h4>
                    <ul class="space-y-2">
                        <li v-for="(file, index) in liquidateForm.attachments" :key="index"
                            class="flex items-center justify-between text-sm text-gray-500 bg-gray-50 p-2 rounded">
                            <span>{{ file.name }}</span>
                            <button type="button" @click="removeLiquidateFile(index)"
                                class="text-red-500 hover:text-red-700">
                                Remove
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button"
                        class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        @click="showLiquidateModal = false">
                        Cancel
                    </button>
                    <button type="submit"
                        class="inline-flex justify-center rounded-md border border-transparent bg-yellow-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
                        :disabled="isSubmitting">
                        {{ isSubmitting ? 'Liquidating...' : 'Liquidate' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Dispose Modal -->
        <Modal :show="showDisposeModal" max-width="xl" @close="showDisposeModal = false">
            <form id="disposeForm" class="p-6 space-y-4" @submit.prevent="submitDisposal">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Dispose Item</h2>

                <!-- Product Info -->
                <div v-if="selectedItem" class="bg-gray-50 p-4 rounded-lg">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Product ID</p>
                            <p class="text-sm text-gray-900">{{ selectedItem.product.productID }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Product Name</p>
                            <p class="text-sm text-gray-900">{{ selectedItem.product.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Source</p>
                            <p class="text-sm text-gray-900">{{ getSourceDisplayName(selectedItem) }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Status</p>
                            <p class="text-sm text-gray-900">{{ selectedItem.status }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                    <input type="number" id="quantity" v-model="disposeForm.quantity" readonly
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :min="1" :max="selectedItem?.quantity" required>
                </div>

                <!-- Note -->
                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700">Note</label>
                    <textarea id="note" v-model="disposeForm.note"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        rows="3" required></textarea>
                </div>

                <!-- Attachments -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Attachments (Optional)</label>
                    <input type="file" ref="attachments" @change="(e) => handleFileChange('dispose', e)"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        multiple>
                </div>

                <!-- Selected Files Preview -->
                <div v-if="disposeForm.attachments.length > 0" class="mt-2">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Selected Files:</h4>
                    <ul class="space-y-2">
                        <li v-for="(file, index) in disposeForm.attachments" :key="index"
                            class="flex items-center justify-between text-sm text-gray-500 bg-gray-50 p-2 rounded">
                            <span>{{ file.name }}</span>
                            <button type="button" @click="removeDisposeFile(index)"
                                class="text-red-500 hover:text-red-700">
                                Remove
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button"
                        class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        @click="showDisposeModal = false">
                        Cancel
                    </button>
                    <button type="submit"
                        class="inline-flex justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                        :disabled="isSubmitting">
                        {{ isSubmitting ? 'Disposing...' : 'Dispose' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import '@/Components/multiselect.css';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import moment from 'moment';
import Modal from '@/Components/Modal.vue';
import { useToast } from 'vue-toastification';

// Component state
const selectedSource = ref(null);
const items = ref([]);
const backOrderInfo = ref(null);
const parentAttachments = ref([]);
const sourceOptions = ref([]);

const toast = useToast();

const groupedItems = computed(() => {
    const result = [];
    // Group items by product, source, and date
    items.value.forEach(item => {
        const existingGroup = result.find(g =>
            g.product.productID === item.product.productID &&
            g.source_id === item.source_id &&
            g.source_type === item.source_type &&
            moment(g.created_at).isSame(item.created_at, 'day')
        );

        if (!existingGroup) {
            result.push({
                id: item.id,
                product: item.product,
                source_id: item.source_id,
                source_type: item.source_type,
                source: item.source,
                created_at: item.created_at,
                back_order_id: item.back_order_id,
                rows: [{
                    id: item.id, // Include the specific row ID
                    quantity: item.quantity,
                    status: item.status,
                    actions: getAvailableActions(item.status),
                    finalized: item.finalized
                }],
            });
        } else {
            existingGroup.rows.push({
                id: item.id, // Include the specific row ID
                quantity: item.quantity,
                status: item.status,
                actions: getAvailableActions(item.status)
            });
        }
    });

    return result;
});

const getAvailableActions = (status) => {
    if (status === 'Missing') return ['Receive', 'Liquidate'];
    if (status === 'Damaged') return ['Receive', 'Dispose'];
    if (status === 'Lost') return ['Receive', 'Dispose'];
    if (status === 'Expired') return ['Receive', 'Dispose'];
    if (status === 'Low quality') return ['Receive', 'Dispose'];
    return ['Receive']; // Default fallback
};

const getBackOrderStatusClass = (status) => {
    switch (status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'processing':
            return 'bg-blue-100 text-blue-800';
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getSourceDisplayName = (item) => {
    if (item.source_type === 'order') {
        return `Order: ${item.source?.order_number || 'N/A'}`;
    } else if (item.source_type === 'transfer') {
        return `Transfer: ${item.source?.transferID || item.source?.transfer_id || 'N/A'}`;
    }
    return 'Unknown Source';
};

const isLoading = ref(false);
const isSubmitting = ref(false);
const showLiquidateModal = ref(false);
const showDisposeModal = ref(false);
const selectedItem = ref(null);

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

const handleFileChange = (formType, e) => {
    const files = Array.from(e.target.files || []);
    if (formType === 'liquidate') {
        liquidateForm.value.attachments = files;
    } else {
        disposeForm.value.attachments = files;
    }
};

const removeLiquidateFile = (index) => {
    liquidateForm.value.attachments.splice(index, 1);
};

const removeDisposeFile = (index) => {
    disposeForm.value.attachments.splice(index, 1);
};

// Component props
const props = defineProps({
    orders: {
        required: true,
        type: Array
    },
    transfers: {
        required: true,
        type: Array
    }
});

// Load source options on mount
onMounted(() => {
    // Combine orders and transfers for source options
    const orderOptions = props.orders.map(order => ({
        id: order.id,
        display_name: `Order: ${order.order_number}`,
        type: 'order',
        ...order
    }));
    
    const transferOptions = props.transfers.map(transfer => ({
        id: transfer.id,
        display_name: `Transfer: ${transfer.transferID || transfer.transfer_id || transfer.id}`,
        type: 'transfer',
        ...transfer
    }));
    
    sourceOptions.value = [...orderOptions, ...transferOptions];
});

// Action handlers
const receiveItems = async (item) => {
    const { value: quantity } = await Swal.fire({
        title: 'Enter Quantity to Receive',
        input: 'number',
        inputLabel: `Maximum quantity: ${item.quantity}`,
        inputValue: item.quantity,
        inputAttributes: {
            min: '1',
            max: item.quantity.toString(),
            step: '1'
        },
        showCancelButton: true,
        confirmButtonText: 'Receive',
        confirmButtonColor: '#059669',
        cancelButtonColor: '#6B7280',
        showLoaderOnConfirm: true,
        preConfirm: async (value) => {
            const num = parseInt(value);
            if (!value || num < 1) {
                Swal.showValidationMessage('Please enter a quantity greater than 0');
                return false;
            }
            if (num > item.quantity) {
                Swal.showValidationMessage(`Cannot receive more than ${item.quantity} items`);
                return false;
            }

            try {
                isLoading.value = true;
                console.log('Sending receive request:', {
                    id: item.id,
                    status: item.status,
                    quantity: num,
                    original_quantity: item.quantity
                });
                console.log('Full item object:', item);
                await axios.post(route('backorders.receive'), {
                    id: item.id, // This is now the specific row ID from the merged object
                    back_order_id: backOrderInfo.value.id,
                    product_id: item.product.id,
                    source_id: item.source_id,
                    source_type: item.source_type,
                    quantity: num,
                    original_quantity: item.quantity,
                    status: item.status
                })
                    .then(response => {
                        Swal.fire({
                            title: 'Success!',
                            text: response.data.message,
                            icon: 'success',
                            confirmButtonColor: '#10B981',
                        });
                    })
                    .catch(error => {
                        console.error('Failed to receive items:', error);
                        Swal.showValidationMessage(error.response?.data?.message || 'Failed to receive items');
                    });
                await handleSourceChange(selectedSource.value);
                return true;
            } catch (error) {
                console.error('Failed to receive items:', error);
                Swal.showValidationMessage(error.response?.data?.message || 'Failed to receive items');
                return false;
            } finally {
                isLoading.value = false;
            }
        },
        allowOutsideClick: () => !Swal.isLoading()
    });
};

// Event handlers
const handleSourceChange = async (source) => {
    if (!source) {
        items.value = [];
        backOrderInfo.value = null;
        return;
    }
    isLoading.value = true;
    let url = '';
    if (source.type === 'order') {
        url = route('backorders.get-back-order.order', source.id);
    } else if (source.type === 'transfer') {
        url = route('backorders.get-back-order.transfer', source.id);
    }
    
    await axios.get(url)
        .then((response) => {
            isLoading.value = false;
            console.log(response.data);

            // Sort items by created_at to ensure consistent grouping
            items.value = response.data.sort((a, b) =>
                new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
            );

            // Extract back order information from the first item (all items should have the same back order)
            if (items.value.length > 0 && items.value[0].back_order) {
                backOrderInfo.value = items.value[0].back_order;
            } else {
                backOrderInfo.value = null;
            }
        })
        .catch((error) => {
            isLoading.value = false;
            items.value = [];
            backOrderInfo.value = null;
            toast.error(error.response?.data || 'Failed to fetch back order items')
        });
};

const submitLiquidation = async () => {
    console.log(selectedItem.value);
    
    // Validate required fields
    if (!selectedItem.value || !selectedItem.value.status) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Status field is required',
            showConfirmButton: true
        });
        return;
    }
    
    if (!liquidateForm.value.note || liquidateForm.value.note.trim() === '') {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Note field is required',
            showConfirmButton: true
        });
        return;
    }
    
    isSubmitting.value = true;
    const formData = new FormData();
    formData.append('id', selectedItem.value.id);
    formData.append('product_id', selectedItem.value.product.id);
    formData.append('source_id', selectedItem.value.source_id);
    formData.append('source_type', selectedItem.value.source_type);
    formData.append('quantity', liquidateForm.value.quantity);
    formData.append('original_quantity', selectedItem.value.quantity);
    formData.append('status', selectedItem.value.status);
    formData.append('note', liquidateForm.value.note);
    
    // Get back_order_id from backOrderInfo or from the item itself
    const backOrderId = backOrderInfo.value?.id || selectedItem.value.back_order_id;
    if (backOrderId) {
        formData.append('back_order_id', backOrderId);
    }

    // Append each attachment
    for (let i = 0; i < liquidateForm.value.attachments.length; i++) {
        formData.append('attachments[]', liquidateForm.value.attachments[i]);
    }

    await axios.post(route('backorders.liquidate'), formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
        .then((response) => {
            isSubmitting.value = false
            showLiquidateModal.value = false;
            Swal.fire({
                icon: 'success',
                title: response.data,
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                handleSourceChange(selectedSource.value);
                liquidateForm.value = {
                    quantity: 0,
                    note: '',
                    attachments: []
                };
            });
        })
        .catch((error) => {
            isSubmitting.value = false
            console.error('Failed to liquidate items:', error);
            Swal.fire({
                icon: 'error',
                title: error.response.data,
                showConfirmButton: false,
                timer: 1500
            });
        });
};

const handleAction = async (action, item) => {
    console.log(item);
    selectedItem.value = item;
    console.log(selectedItem.value);
    console.log(backOrderInfo.value);

    switch (action) {
        case 'Receive':
            await receiveItems(item);
            break;

        case 'Liquidate':
            liquidateForm.value = {
                quantity: item.quantity,
                note: '',
                attachments: [],
                ...item
            };
            showLiquidateModal.value = true;
            break;

        case 'Dispose':
            disposeForm.value = {
                quantity: item.quantity,
                note: '',
                attachments: [],
                ...item
            };
            showDisposeModal.value = true;
            break;
    }
};

const submitDisposal = async () => {
    console.log(selectedItem.value);
    
    // Validate required fields
    if (!selectedItem.value || !selectedItem.value.status) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Status field is required',
            showConfirmButton: true
        });
        return;
    }
    
    if (!disposeForm.value.note || disposeForm.value.note.trim() === '') {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Note field is required',
            showConfirmButton: true
        });
        return;
    }
    
    isSubmitting.value = true;
    const formData = new FormData();
    formData.append('id', selectedItem.value.id);
    formData.append('product_id', selectedItem.value.product.id);
    formData.append('source_id', selectedItem.value.source_id);
    formData.append('source_type', selectedItem.value.source_type);
    formData.append('note', disposeForm.value.note);
    formData.append('status', selectedItem.value.status); // Changed from 'type' to 'status'
    formData.append('quantity', selectedItem.value.quantity);

    // Get back_order_id from backOrderInfo or from the item itself
    const backOrderId = backOrderInfo.value?.id || selectedItem.value.back_order_id;
    if (backOrderId) {
        formData.append('back_order_id', backOrderId);
    }

    // Append each attachment
    for (let i = 0; i < disposeForm.value.attachments.length; i++) {
        formData.append('attachments[]', disposeForm.value.attachments[i]);
    }

    await axios.post(route('backorders.dispose'), formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
        .then((response) => {
            isSubmitting.value = false
            showDisposeModal.value = false;
            Swal.fire({
                icon: 'success',
                title: response.data,
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                disposeForm.value = {
                    quantity: 0,
                    note: '',
                    attachments: []
                };
                handleSourceChange(selectedSource.value);
            });
        })
        .catch((error) => {
            isSubmitting.value = false
            console.error('Failed to dispose items:', error);
            Swal.fire({
                icon: 'error',
                title: error.response.data,
                showConfirmButton: false,
                timer: 1500
            });
        });
};

function handleParentAttachments(e) {
    parentAttachments.value = Array.from(e.target.files || []);
}

function removeParentAttachment(index) {
    parentAttachments.value.splice(index, 1);
}

const isUploading = ref(false);

async function uploadParentAttachments() {
    if (!backOrderInfo.value || parentAttachments.value.length === 0) return;
    const result = await Swal.fire({
        title: 'Upload Attachments?',
        text: 'Are you sure you want to upload these attachments to the back order?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, upload!'
    });
    if (!result.isConfirmed) return;
    isUploading.value = true;
    const formData = new FormData();
    parentAttachments.value.forEach(file => formData.append('attachments[]', file));
    try {
        const { data } = await axios.post(route('backorders.uploadAttachment', backOrderInfo.value.id), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        parentAttachments.value = [];
        if (backOrderInfo.value.attach_documents) {
            backOrderInfo.value.attach_documents = data.files;
        }
        toast.success(data.message);
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to upload attachments');
    } finally {
        isUploading.value = false;
    }
}

async function deleteParentAttachment(filePath) {
    const result = await Swal.fire({
        title: 'Delete Attachment?',
        text: 'Are you sure you want to delete this attachment? This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    });
    if (!result.isConfirmed) return;
    try {
        const { data } = await axios.delete(route('backorders.deleteAttachment', backOrderInfo.value.id), {
            data: { file_path: filePath }
        });
        if (backOrderInfo.value.attach_documents) {
            backOrderInfo.value.attach_documents = data.files;
        }
        toast.success(data.message);
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to delete attachment');
    }
}

</script>

<style>
.loader {
  border: 2px solid #f3f3f3;
  border-top: 2px solid #3498db;
  border-radius: 50%;
  width: 16px;
  height: 16px;
  animation: spin 1s linear infinite;
  display: inline-block;
}
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style> 