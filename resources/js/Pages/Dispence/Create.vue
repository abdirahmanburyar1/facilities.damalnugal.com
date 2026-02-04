<template>
    <AuthenticatedLayout title="Patient Dispensing" description="Manage patient medication dispensing records"
        img="/assets/images/dispence.png">
        
        <!-- Modern Header Section -->
        <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Breadcrumb Navigation -->
                <nav class="flex items-center space-x-2 text-sm text-gray-600 mb-8">
                    <Link :href="route('dispence.index')"
                        class="flex items-center hover:text-blue-600 transition-colors duration-200">
                        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Dispenses
                    </Link>
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-gray-900 font-medium">Create New</span>
                </nav>

                <!-- Page Header -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden mb-8">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="bg-white/20 rounded-xl p-3">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold text-white">Create New Dispense</h1>
                                    <p class="text-blue-100 mt-1">Issue medication to patients with proper documentation
                                    </p>
                                </div>
                            </div>
                            <div class="bg-white/10 rounded-xl px-4 py-2">
                                <div class="flex items-center text-white text-sm">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ new Date().toLocaleDateString('en-US', {
                                        weekday: 'long',
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric'
                                    }) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submit" novalidate class="divide-y divide-gray-100">
                        <!-- Patient Information -->
                        <div class="p-8">
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Patient Information
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <!-- Patient Name -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">
                                            Patient Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" v-model="form.patient_name"
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-200"
                                            placeholder="Enter patient full name" required />
                                        <InputError :message="errors.patient_name" class="mt-1 text-sm text-red-600" />
                                    </div>

                                    <!-- Age -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">
                                            Age <span class="text-red-500">*</span>
                                        </label>
                                        <div class="flex rounded-lg shadow-sm">
                                            <input type="number" v-model="form.patient_age" min="0" max="120"
                                                class="block w-full rounded-l-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200"
                                                placeholder="Age" required />
                                            <span
                                                class="inline-flex items-center px-3 rounded-r-lg border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                                Years
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Gender -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">
                                            Gender <span class="text-red-500">*</span>
                                        </label>
                                        <select v-model="form.patient_gender"
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-200"
                                            required>
                                            <option value="">Select Gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                        </select>
                                    </div>

                                    <!-- Phone Number -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">
                                            Phone Number <span class="text-red-500">*</span>
                                        </label>
                                        <input type="tel" v-model="form.phone_number"
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-200"
                                            placeholder="Enter phone number" required />
                                        <InputError :message="errors.phone_number" class="mt-1 text-sm text-red-600" />
                                    </div>

                                    <!-- Prescription Date -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">
                                            Prescription Date <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" v-model="form.prescription_date" :max="today"
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-200"
                                            required />
                                    </div>
                                </div>

                                <!-- Diagnosis -->
                                <div class="mt-6 space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Diagnosis</label>
                                    <textarea v-model="form.diagnosis" rows="3"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-200"
                                        placeholder="Enter patient diagnosis or medical condition"></textarea>
                                    <InputError :message="errors.diagnosis" class="mt-1 text-sm text-red-600" />
                                </div>
                            </div>
                        </div>

                        <!-- Prescription Items -->
                        <div class="p-8 bg-gray-50">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    Prescription Items
                                </h3>
                                <button type="button" @click="addItem"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200"
                                    :disabled="isProcessing || haveIssue.length > 0">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Add Item
                                </button>
                            </div>

                            <div class="">
                                <table class="min-w-full divide-y divide-gray-200">
                                                                             <thead class="bg-gray-50">
                                         <tr>
                                             <th class="w-2/5 px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                 Product
                                             </th>
                                             <th class="w-3/5 px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                 Dosage Information
                                             </th>
                                             <th class="w-16 px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                 Actions
                                             </th>
                                         </tr>
                                     </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                                                                     <tr v-for="(item, index) in form.items" :key="index" 
                                             class="hover:bg-gray-50 transition-colors duration-200">
                                             <td class="w-2/5 px-6 py-4">
                                                 <div class="w-full">
                                                     <Multiselect v-model="item.product" :options="props.inventories"
                                                         :searchable="true" :close-on-select="true" :allow-empty="true"
                                                         placeholder="Select product" track-by="id" label="name"
                                                         @select="checkInventory(index, $event)" 
                                                         class="text-sm w-full">
                                                         <template v-slot:option="{ option }">
                                                             <div class="flex justify-between items-center w-full">
                                                                 <span class="truncate">{{ option.name }}</span>
                                                                 <span
                                                                     class="ml-2 text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                                                                     Stock: {{ option.quantity }}
                                                                 </span>
                                                             </div>
                                                         </template>
                                                     </Multiselect>
                                                 </div>
                                             </td>
                                             <td class="w-3/5 px-6 py-4">
                                                 <div class="flex items-center space-x-4">
                                                     <div class="space-y-2 flex-1">
                                                         <label class="block text-xs font-medium text-gray-700">Dose</label>
                                                         <input type="number"
                                                             class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-all duration-200"
                                                             v-model="item.dose" placeholder="Dose" min="1"
                                                             @input="calculateItemQuantity(index)" required />
                                                     </div>
                                                     <div class="space-y-2 flex-1">
                                                         <label class="block text-xs font-medium text-gray-700">Frequency</label>
                                                         <input type="number"
                                                             class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-all duration-200"
                                                             v-model="item.frequency" placeholder="Per day" min="1"
                                                             @input="calculateItemQuantity(index)" required />
                                                     </div>
                                                     <div class="space-y-2 flex-1">
                                                         <label class="block text-xs font-medium text-gray-700">Duration</label>
                                                         <input type="number"
                                                             class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-all duration-200"
                                                             v-model="item.duration" placeholder="Days" min="1"
                                                             @input="calculateItemQuantity(index)" required />
                                                     </div>
                                                     <div class="space-y-2 flex-1">
                                                         <label class="block text-xs font-medium text-gray-700">Quantity</label>
                                                         <input type="number"
                                                             class="block w-full rounded-lg border-gray-200 bg-gray-50 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-all duration-200"
                                                             v-model="item.quantity" readonly required
                                                             @input="checkAvailability(index, item)" />
                                                         <span v-if="haveIssue[index] != null" 
                                                             class="text-xs text-red-500 block mt-1">
                                                             {{ haveIssue[index] }}
                                                         </span>
                                                     </div>
                                                 </div>
                                             </td>
                                                                                             <td class="w-16 px-6 py-4 text-center">
                                                 <button type="button" @click="removeItem(index)"
                                                     class="text-red-600 hover:text-red-900 p-2 rounded-lg hover:bg-red-50 transition-all duration-200"
                                                     :disabled="form.items.length <= 1">
                                                     <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                         stroke="currentColor">
                                                         <path stroke-linecap="round" stroke-linejoin="round"
                                                             stroke-width="2"
                                                             d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                     </svg>
                                                 </button>
                                             </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Summary Card -->
                            <div class="mt-6 bg-blue-50 rounded-xl p-4 border border-blue-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="text-sm font-medium text-blue-900">Summary</span>
                                    </div>
                                    <div class="text-sm text-blue-700">
                                        Total Items: {{ form.items.filter(item => item.product).length }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="bg-gray-50 px-8 py-6 border-t border-gray-200 rounded-b-lg flex justify-end space-x-4">
                            <Link :href="route('dispence.index')"
                                class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200"
                                :disabled="isProcessing">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Cancel
                            </Link>
                            <button type="submit"
                                class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200"
                                :disabled="isProcessing || haveIssue.length > 0">
                                <svg v-if="isProcessing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <svg v-else class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                {{ isProcessing ? 'Processing...' : 'Save Dispense' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import '@/Components/multiselect.css';
import { useToast } from 'vue-toastification';
import Swal from 'sweetalert2';
import axios from 'axios';

const toast = useToast();
const today = new Date().toISOString().split('T')[0];

const props = defineProps({
    inventories: {
        type: Array,
        required: true
    },
});

const errors = ref({});
const isProcessing = ref(false);
const form = ref({
    patient_name: '',
    patient_age: '',
    patient_gender: '',
    diagnosis: '',
    phone_number: '',
    prescription_date: today,
    items: [{
        product_id: '',
        product: null,
        dose: '',
        frequency: '',
        duration: '',
        quantity: 1,
        max_quantity: null
    }]
});

const addItem = () => {
    form.value.items.push({
        product_id: '',
        product: null,
        dose: '',
        frequency: '',
        duration: '',
        quantity: 1
    });
};

const removeItem = (index) => {
    if (form.value.items.length > 1) {
        form.value.items.splice(index, 1);
    }
};

const calculateItemQuantity = (index) => {
    const item = form.value.items[index];
    if (item.dose && item.frequency && item.duration) {
        const calculatedQty = item.dose * item.frequency * item.duration;
        item.quantity = calculatedQty;

        // Check if quantity exceeds available stock
        if (item.dose && item.frequency && item.duration) {
            checkAvailability(index, item)
        }
    }
};

async function checkInventory(index, selected) {
    // Clear row data first
    const item = form.value.items[index];
    item.product = null;
    item.product_id = '';
    item.dose = '';
    item.frequency = '';
    item.duration = '';
    item.quantity = 1;
    item.max_quantity = null;

    if (selected) {
        // Check if this product is already selected in another row
        const isDuplicate = form.value.items.some((item, idx) =>
            idx !== index && item.product_id === selected.id
        );

        if (isDuplicate) {
            Swal.fire({
                title: 'Duplicate Item',
                text: 'This product is already added to the list',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Set the new product data
        item.product = selected;
        item.product_id = selected.id;
        item.max_quantity = parseInt(selected.quantity);
        calculateItemQuantity(index);
        addItem();
        return;
    }
}

const isLoading = ref(false);
// Watch for changes in dose, frequency, and duration
watch(() => form.value.items, (items) => {
    items.forEach((item, index) => {
        if (item.dose && item.frequency && item.duration) {
            calculateItemQuantity(index);
        }
    });
}, { deep: true });

const haveIssue = ref([]);

async function checkAvailability(index, item) {
    haveIssue.value = [];
    await axios.post(route('dispence.check-invnetory'), {
        quantity: item.quantity,
        product_id: item.product_id
    })
        .then((response) => {
            if (response.data < item.quantity) {
                haveIssue.value[index] = `Only ${response.data} quantities are available`;
            }
        })
        .catch((error) => {
            console.log(error.response.data);
        })
}

const submit = async () => {
    isProcessing.value = true;
    await axios.post(route('dispence.store'), form.value)
        .then((response) => {
            isProcessing.value = false;
            toast.success(response.data);
            reloadDispences();
        })
        .catch((error) => {
            isProcessing.value = false;
            console.error('Error creating dispense:', error);
            toast.error('Error creating dispense');
        });
};

function reloadDispences() {
    form.value = {
        patient_name: '',
        patient_age: '',
        patient_gender: '',
        phone_number: '',
        diagnosis: '',
        prescription_date: today,
        items: [{
            product_id: '',
            product: null,
            dose: '',
            frequency: '',
            duration: '',
            quantity: 1
        }]
    }
    router.get(route('dispence.create'), {}, {
        preserveState: true,
        preserveScroll: true,
        only: ['inventories']
    })
}
</script>