<template>
    <Head title="Add New Supply" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add New Supply</h2>
                <Link :href="route('supplies.index')" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Back to Supplies
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Product Selection -->
                                <div>
                                    <InputLabel for="product_id" value="Product" />
                                    <SelectInput
                                        id="product_id"
                                        v-model="form.product_id"
                                        :options="productOptions"
                                        class="mt-1 block w-full"
                                        placeholder="Select a product"
                                        required
                                    />
                                    <InputError :message="form.errors.product_id" class="mt-2" />
                                </div>

                                <!-- Warehouse Selection -->
                                <div>
                                    <InputLabel for="warehouse_id" value="Warehouse" />
                                    <SelectInput
                                        id="warehouse_id"
                                        v-model="form.warehouse_id"
                                        :options="warehouseOptions"
                                        class="mt-1 block w-full"
                                        placeholder="Select a warehouse"
                                        required
                                    />
                                    <InputError :message="form.errors.warehouse_id" class="mt-2" />
                                </div>

                                <!-- Supplier Selection -->
                                <div>
                                    <InputLabel for="supplier_id" value="Supplier" />
                                    <SelectInput
                                        id="supplier_id"
                                        v-model="form.supplier_id"
                                        :options="supplierOptions"
                                        class="mt-1 block w-full"
                                        placeholder="Select a supplier"
                                        required
                                    />
                                    <InputError :message="form.errors.supplier_id" class="mt-2" />
                                </div>

                                <!-- Quantity -->
                                <div>
                                    <InputLabel for="quantity" value="Quantity" />
                                    <TextInput
                                        id="quantity"
                                        type="number"
                                        v-model="form.quantity"
                                        class="mt-1 block w-full"
                                        min="1"
                                        required
                                    />
                                    <InputError :message="form.errors.quantity" class="mt-2" />
                                </div>

                                <!-- Unit Price -->
                                <div>
                                    <InputLabel for="unit_price" value="Unit Price" />
                                    <TextInput
                                        id="unit_price"
                                        type="number"
                                        step="0.01"
                                        v-model="form.unit_price"
                                        class="mt-1 block w-full"
                                        min="0"
                                        required
                                    />
                                    <InputError :message="form.errors.unit_price" class="mt-2" />
                                </div>

                                <!-- Total Price (Calculated) -->
                                <div>
                                    <InputLabel for="total_price" value="Total Price" />
                                    <TextInput
                                        id="total_price"
                                        type="number"
                                        step="0.01"
                                        :value="calculateTotalPrice"
                                        class="mt-1 block w-full bg-gray-100"
                                        readonly
                                    />
                                </div>

                                <!-- Supply Date -->
                                <div>
                                    <InputLabel for="supply_date" value="Supply Date" />
                                    <TextInput
                                        id="supply_date"
                                        type="date"
                                        v-model="form.supply_date"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.supply_date" class="mt-2" />
                                </div>

                                <!-- Invoice Number -->
                                <div>
                                    <InputLabel for="invoice_number" value="Invoice Number" />
                                    <TextInput
                                        id="invoice_number"
                                        type="text"
                                        v-model="form.invoice_number"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.invoice_number" class="mt-2" />
                                </div>

                                <!-- Batch Number -->
                                <div>
                                    <InputLabel for="batch_number" value="Batch Number" />
                                    <TextInput
                                        id="batch_number"
                                        type="text"
                                        v-model="form.batch_number"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.batch_number" class="mt-2" />
                                </div>

                                <!-- Manufacturing Date -->
                                <div>
                                    <InputLabel for="manufacturing_date" value="Manufacturing Date" />
                                    <TextInput
                                        id="manufacturing_date"
                                        type="date"
                                        v-model="form.manufacturing_date"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.manufacturing_date" class="mt-2" />
                                </div>

                                <!-- Expiry Date -->
                                <div>
                                    <InputLabel for="expiry_date" value="Expiry Date" />
                                    <TextInput
                                        id="expiry_date"
                                        type="date"
                                        v-model="form.expiry_date"
                                        class="mt-1 block w-full"
                                        :min="form.manufacturing_date"
                                    />
                                    <InputError :message="form.errors.expiry_date" class="mt-2" />
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="mt-6">
                                <InputLabel for="notes" value="Notes" />
                                <TextareaInput
                                    id="notes"
                                    v-model="form.notes"
                                    :rows="3"
                                    class="mt-1 block w-full"
                                    placeholder="Enter any additional notes"
                                />
                                <InputError :message="form.errors.notes" class="mt-2" />
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center justify-end mt-6">
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Add Supply
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import TextareaInput from '@/Components/TextareaInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    products: Array,
    warehouses: Array,
    suppliers: Array,
});

// Initialize form with default values
const form = useForm({
    product_id: '',
    warehouse_id: '',
    supplier_id: '',
    quantity: '',
    unit_price: '',
    supply_date: new Date().toISOString().substr(0, 10), // Default to today
    invoice_number: '',
    batch_number: '',
    manufacturing_date: '',
    expiry_date: '',
    notes: '',
});

// Computed properties
const productOptions = computed(() => {
    return props.products.map(product => ({
        value: product.id,
        label: product.name
    }));
});

const warehouseOptions = computed(() => {
    return props.warehouses.map(warehouse => ({
        value: warehouse.id,
        label: warehouse.name
    }));
});

const supplierOptions = computed(() => {
    return props.suppliers.map(supplier => ({
        value: supplier.id,
        label: supplier.name
    }));
});

const calculateTotalPrice = computed(() => {
    if (form.quantity && form.unit_price) {
        return (parseFloat(form.quantity) * parseFloat(form.unit_price)).toFixed(2);
    }
    return '0.00';
});

// Submit form
const submit = () => {
    form.post(route('supplies.store'), {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>
