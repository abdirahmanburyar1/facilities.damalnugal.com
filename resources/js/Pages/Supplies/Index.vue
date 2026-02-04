<template>

    <Head title="Supplies" />

    <AuthenticatedLayout>
        <div class="bg-white overflow-hidden sm:rounded-lg">
            <div class="text-gray-900">
                <!-- Tabs -->
                <div class="border-b border-gray-200 mb-4">
                    <nav class="-mb-px flex space-x-8">
                        <button @click="switchTab('supplies')"
                            :class="[currentTab === 'supplies' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                            Supplies
                        </button>
                        <button @click="switchTab('suppliers')"
                            :class="[currentTab === 'suppliers' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                            Suppliers
                        </button>
                    </nav>
                </div>

                <!-- Supplies Tab -->
                <div v-if="currentTab === 'supplies'">
                    <!-- Filters -->
                    <div class="flex flex-col md:flex-row items-center gap-2 p-2">
                        <TextInput id="search" type="text" v-model="supplyFilters.search" class="w-full"
                            placeholder="Search supplies..." @keyup.enter="getSupplies" />
                        <div class="flex items-center gap-2 w-full">
                            <TextInput id="date_from" type="date" v-model="supplyFilters.date_from" class="w-full" />
                            <span class="whitespace-nowrap">To</span>
                            <TextInput id="date_to" type="date" v-model="supplyFilters.date_to" class="w-full" />
                        </div>
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <PrimaryButton @click="getSupplies" class="w-full">
                                Filter
                            </PrimaryButton>
                            <SecondaryButton @click="resetSupplyFilters" class="w-full">
                                Reset
                            </SecondaryButton>
                            <PrimaryButton @click="openCreateSupplyModal"
                                class="w-10 h-10 flex items-center justify-center">
                                +
                            </PrimaryButton>
                        </div>
                    </div>
                    <!-- Supplies Table -->
                    <div class="overflow-x-auto bg-white rounded-lg shadow">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" 
                                            :checked="isAllSelected"
                                            @change="toggleSelectAll"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Supplier
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Warehouse
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Invoice
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="supply in props.supplies.data" :key="supply.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" 
                                            :checked="selectedSupplies.includes(supply.id)"
                                            @change="() => toggleSupply(supply.id)"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ supply.supplier.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Products: {{ supply.items.length }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ supply.warehouse.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ supply.invoice_number || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ formatDate(supply.supply_date) }}
                                        </div>
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium flex justify-end">
                                        <div class="flex items-center space-x-3">
                                            <button type="button"
                                                class="text-blue-600 hover:text-blue-900 inline-flex items-center"
                                                @click="openViewItemsModal(supply)">
                                                <i class="fas fa-eye w-5 h-5"></i>
                                                <span class="ml-1">View</span>
                                            </button>
                                            <button type="button"
                                                class="text-indigo-600 hover:text-indigo-900 inline-flex items-center"
                                                @click="openEditSupplyModal(supply)">
                                                <i class="fas fa-edit w-5 h-5"></i>
                                                <span class="ml-1">Edit</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="props.supplies.data.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-lg font-medium">No supplies found</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Floating Delete Button -->
                    <div v-if="selectedSupplies.length > 0"
                        class="fixed bottom-20 left-1/2 transform -translate-x-1/2 z-50 flex items-center bg-white rounded-lg shadow-lg border border-gray-200 px-4 py-2 space-x-2">
                        <span class="text-sm text-gray-600">{{ selectedSupplies.length }} supplies selected</span>
                        <button @click="confirmBulkDelete"
                            class="inline-flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete
                        </button>
                    </div>
                </div>

                <!-- Suppliers Tab -->
                <div v-if="currentTab === 'suppliers'">
                    <!-- Filters -->
                    <div class="flex flex-col md:flex-row items-center gap-2 p-2">
                        <TextInput id="supplier_search" type="text" v-model="supplierFilters.search" class="w-full"
                            placeholder="Search suppliers..." @keyup.enter="getSuppliers" />
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <PrimaryButton @click="getSuppliers">
                                Filter
                            </PrimaryButton>
                            <SecondaryButton @click="resetSupplierFilters">
                                Reset
                            </SecondaryButton>
                            <PrimaryButton @click="openCreateSupplierModal"
                                class="w-10 h-10 flex items-center justify-center">
                                +
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- Suppliers Table -->
                    <div class="overflow-x-auto bg-white rounded-lg shadow">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" v-model="selectAllSuppliers"
                                            @change="toggleSelectAllSuppliers"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Name</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Contact Person</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Email</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Phone</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="supplier in props.suppliers?.data" :key="supplier.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" :value="supplier.id" v-model="selectedSuppliers"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ supplier.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ supplier.contact_person }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ supplier.email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ supplier.phone }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openEditSupplierModal(supplier)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-2">
                                            Edit
                                        </button>
                                        <button @click="confirmDeleteSupplier(supplier)"
                                            class="text-red-600 hover:text-red-900">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Floating Delete Button -->
                    <div v-if="selectedSuppliers.length > 0"
                        class="fixed bottom-20 left-1/2 transform -translate-x-1/2 z-50 flex items-center bg-white rounded-lg shadow-lg border border-gray-200 px-4 py-2 space-x-2">
                        <span class="text-sm text-gray-600">{{ selectedSuppliers.length }} suppliers selected</span>
                        <button @click="confirmBulkDeleteSuppliers"
                            class="inline-flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Supply Modal -->
        <Modal :show="showSupplyModal" @close="closeSupplyModal" maxWidth="70%">
            <div class="p-4">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ supplyForm.id ? 'Edit' : 'Add New' }} Supply
                </h2>

                <form @submit.prevent="submitSupply">
                    <!-- Common Fields -->
                    <div class="mb-4">
                        <InputLabel for="supplier_id" value="Supplier" />
                        <select id="supplier_id" v-model="supplyForm.supplier_id" class="mt-1 block w-full"
                            placeholder="Select supplier" required>
                            <option v-for="supplier in props.suppliers?.data" :key="supplier.id" :value="supplier.id">
                                {{ supplier.name }}
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <InputLabel for="invoice_number" value="Invoice Number" />
                            <TextInput id="invoice_number" type="text" v-model="supplyForm.invoice_number"
                                class="mt-1 block w-full" placeholder="Enter invoice number" :disabled="isSubmitting" />
                        </div>
                        <div>
                            <InputLabel for="supply_date" value="Supply Date" />
                            <TextInput id="supply_date" type="date" v-model="supplyForm.supply_date"
                                class="mt-1 block w-full" required :disabled="isSubmitting" />
                        </div>
                    </div>

                    <!-- Supply Items -->
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-4">
                            <div class="flex items-center gap-4">
                                <h3 class="text-lg font-semibold text-gray-900">Supply Items</h3>
                                <span class="text-sm text-gray-500">
                                    {{ supplyForm.items.length }} item{{ supplyForm.items.length !== 1 ? 's' : '' }}
                                </span>
                            </div>
                            <SecondaryButton 
                                type="button" 
                                @click="addProduct" 
                                :disabled="isSubmitting"
                                class="inline-flex items-center px-4 py-2 bg-indigo-50 hover:bg-indigo-100"
                            >
                                <i class="fas fa-plus w-4 h-4 mr-2"></i>
                                Add New Item
                            </SecondaryButton>
                        </div>

                        <!-- Empty State -->
                        <div v-if="supplyForm.items.length === 0" 
                            class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                            <div class="space-y-1">
                                <i class="fas fa-box text-gray-400 text-3xl mb-2"></i>
                                <h3 class="text-sm font-medium text-gray-900">No items added</h3>
                                <p class="text-sm text-gray-500">Click "Add New Item" to start adding items to this supply</p>
                            </div>
                        </div>

                        <!-- Items List -->
                        <div v-else class="space-y-4">
                            <div v-for="(item, index) in supplyForm.items" 
                                :key="index"
                                class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200"
                            >
                                <div class="flex flex-wrap items-end gap-4">
                                    <!-- Product Selection -->
                                    <div class="flex-1 min-w-[200px]">
                                        <InputLabel :for="`product_${index}`" value="Item" class="text-gray-700" />
                                        <Multiselect 
                                            v-model="item.product_id" 
                                            :options="searchResults" 
                                            :searchable="true"
                                            :loading="isLoading" 
                                            :internal-search="false" 
                                            :clear-on-select="true"
                                            :close-on-select="true" 
                                            :options-limit="300" 
                                            :limit="3" 
                                            :max-height="600"
                                            :show-no-results="true" 
                                            :hide-selected="true" 
                                            @search-change="searchProduct"
                                            placeholder="Search product by name or barcode" 
                                            label="product_name"
                                            track-by="product_id" 
                                            :preselect-first="false"
                                            @select="selectProduct(index, $event)"
                                            class="mt-1"
                                        >
                                            <template v-slot:noResult>
                                                <div class="text-gray-500">No products found</div>
                                            </template>
                                        </Multiselect>
                                    </div>

                                    <!-- Batch Number -->
                                    <div class="w-40">
                                        <InputLabel :for="`batch_number_${index}`" value="Batch Number" class="text-gray-700" />
                                        <TextInput 
                                            :id="`batch_number_${index}`" 
                                            v-model="item.batch_number"
                                            class="mt-1 block w-full" 
                                            placeholder="Enter batch number" 
                                            required 
                                        />
                                    </div>

                                    <!-- Quantity -->
                                    <div class="w-32">
                                        <InputLabel :for="`quantity_${index}`" value="Quantity" class="text-gray-700" />
                                        <TextInput 
                                            :id="`quantity_${index}`" 
                                            type="number" 
                                            v-model="item.quantity"
                                            class="mt-1 block w-full" 
                                            placeholder="Qty" 
                                            min="1" 
                                            required
                                            :disabled="isSubmitting || (item.id && item.status !== 'pending')" 
                                        />
                                    </div>

                                    <!-- Manufacturing Date -->
                                    <div class="w-40">
                                        <InputLabel :for="`manufacturing_date_${index}`" value="Manufacturing Date" class="text-gray-700" />
                                        <TextInput 
                                            :id="`manufacturing_date_${index}`" 
                                            type="date"
                                            v-model="item.manufacturing_date" 
                                            class="mt-1 block w-full"
                                            :disabled="isSubmitting" 
                                        />
                                    </div>

                                    <!-- Expiry Date -->
                                    <div class="w-40">
                                        <InputLabel :for="`expiry_date_${index}`" value="Expiry Date" class="text-gray-700" />
                                        <TextInput 
                                            :id="`expiry_date_${index}`" 
                                            type="date" 
                                            v-model="item.expiry_date"
                                            class="mt-1 block w-full" 
                                            :disabled="isSubmitting" 
                                        />
                                    </div>

                                    <!-- Remove Button -->
                                    <div class="flex-none">
                                        <button 
                                            type="button" 
                                            @click="removeProduct(index)"
                                            class="inline-flex items-center px-3 py-2 border border-red-200 rounded-md text-red-600 hover:text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                            :disabled="isSubmitting || (item.id && item.status !== 'pending') || supplyForm.items.length === 1"
                                            :title="supplyForm.items.length === 1 ? 'Cannot remove the last item' : ''"
                                        >
                                            <i class="fas fa-trash w-4 h-4"></i>
                                            <span class="ml-1">Remove</span>
                                        </button>
                                    </div>

                                    <!-- Add Item Button -->
                                    <div class="flex-none">
                                        <SecondaryButton 
                                            type="button" 
                                            @click="addProduct" 
                                            :disabled="isSubmitting"
                                            class="h-[38px]"
                                        >
                                            <i class="fas fa-plus w-4 h-4 mr-1"></i>
                                            Add
                                        </SecondaryButton>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <InputLabel for="notes" value="Notes" />
                        <TextareaInput id="notes" v-model="supplyForm.notes" class="mt-1 block w-full" :rows="3"
                            placeholder="Enter any additional notes about this supply" :disabled="isSubmitting" />
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-end">
                        <SecondaryButton @click="closeSupplyModal" class="mr-3" :disabled="isSubmitting">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-50 cursor-not-allowed': isSubmitting }"
                            :disabled="isSubmitting || supplyForm.items.length === 0">
                            <span v-if="isSubmitting" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4">
                                    </circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Processing...
                            </span>
                            <span v-else>{{ supplyForm.id ? 'Update' : 'Add' }} Supply</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Create Supplier Modal -->
        <Modal :show="showCreateSupplierModal" @close="closeCreateSupplierModal" maxWidth="7xl">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ form.id ? 'Edit Supplier' : 'Add New Supplier' }}
                </h2>

                <form @submit.prevent="submitSupplierForm">
                    <!-- Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <InputLabel for="name" value="Name" />
                            <TextInput id="name" type="text" v-model="form.name" class="mt-1 block w-full" required
                                placeholder="Enter supplier name" :disabled="isSubmitting" />
                            <div v-if="form.errors.name" class="text-sm text-red-600 mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <InputLabel for="contact_person" value="Contact Person" />
                            <TextInput id="contact_person" type="text" v-model="form.contact_person"
                                placeholder="Enter contact person name" class="mt-1 block w-full"
                                :disabled="isSubmitting" />
                            <div v-if="form.errors.contact_person" class="text-sm text-red-600 mt-1">{{
                                form.errors.contact_person }}</div>
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput id="email" type="email" v-model="form.email" class="mt-1 block w-full"
                                placeholder="Enter email address" :disabled="isSubmitting" />
                            <div v-if="form.errors.email" class="text-sm text-red-600 mt-1">{{ form.errors.email }}
                            </div>
                        </div>

                        <div>
                            <InputLabel for="phone" value="Phone" />
                            <TextInput id="phone" type="text" v-model="form.phone" class="mt-1 block w-full"
                                placeholder="Enter phone number" :disabled="isSubmitting" />
                            <div v-if="form.errors.phone" class="text-sm text-red-600 mt-1">{{ form.errors.phone }}
                            </div>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <InputLabel for="address" value="Address" />
                            <TextareaInput id="address" v-model="form.address" class="mt-1 block w-full" :rows="3"
                                placeholder="Enter complete address" :disabled="isSubmitting" />
                            <div v-if="form.errors.address" class="text-sm text-red-600 mt-1">{{ form.errors.address }}
                            </div>
                        </div>

                        <div>
                            <InputLabel for="notes" value="Notes" />
                            <TextareaInput id="notes" v-model="form.notes" class="mt-1 block w-full" :rows="3"
                                placeholder="Enter additional notes" :disabled="isSubmitting" />
                            <div v-if="form.errors.notes" class="text-sm text-red-600 mt-1">{{ form.errors.notes }}
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.is_active" :disabled="isSubmitting"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <span class="ml-2 text-sm text-gray-600">Active</span>
                        </label>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end mt-6 gap-3">
                        <button type="button" @click="closeCreateSupplierModal"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                            :disabled="isSubmitting">
                            Cancel
                        </button>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            :disabled="isSubmitting">
                            {{ form.id ? isSubmitting ? 'Updating...' : 'Update' : isSubmitting ? 'Creating...' :
                                'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- View Supply Items Modal -->
        <Modal :show="showViewItemsModal" @close="closeViewItemsModal" maxWidth="7xl">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Supply Items
                </h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Product
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Quantity
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Batch Number
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Manufacturing Date
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Expiry Date
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody v-if="selectedSupply.items && selectedSupply.items.length > 0"
                            class="bg-white divide-y divide-gray-200">
                            <tr v-for="item in selectedSupply.items" :key="item.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ item.product.name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ item.quantity }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ item.batch_number }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ formatDate(item.manufacturing_date) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ formatDate(item.expiry_date) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[
                                        'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                        item.status === 'approved'
                                            ? 'bg-green-100 text-green-800'
                                            : item.status === 'rejected'
                                                ? 'bg-red-100 text-red-800'
                                                : 'bg-yellow-100 text-yellow-800'
                                    ]">
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td v-if="item.status !== 'approved'" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" >
                                    <button @click="approveItem(item)"
                                        class="text-green-600 hover:text-green-900 mr-2">
                                        Approve
                                    </button>
                                    <button @click="rejectItem(item)"
                                        class="text-red-600 hover:text-red-900">
                                        Reject
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tbody v-else>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                    {{ selectedSupply.items ? 'No items found' : 'Loading items...' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeViewItemsModal">Close</SecondaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>

</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextareaInput from '@/Components/TextareaInput.vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import moment from 'moment';

// Initialize toast
const toast = useToast();

const props = defineProps({
    supplies: {
        type: Object,
        required: true,
        default: () => ({
            data: [],
            meta: { links: [] }
        })
    },
    suppliers: {
        type: Object,
        required: true,
        default: () => ({
            data: [],
            meta: { links: [] }
        })
    },
    supplyFilters: {
        type: Object,
        default: () => ({})
    },
    supplierFilters: {
        type: Object,
        default: () => ({})
    },
    warehouses: {
        type: Array,
        default: () => []
    },
    products: {
        type: Array,
        default: () => []
    },
    activeTab: {
        type: String,
        default: 'supplies'
    }
});

// Active tab state - initialized with 'supplies' as default
const currentTab = ref(props.activeTab);

// Watch for prop changes and tab switches
const switchTab = (tab) => {
    currentTab.value = tab;
    if (tab === 'supplies') {
        getSupplies();
    } else if (tab === 'suppliers') {
        getSuppliers();
    }
};

// Initial data load
onMounted(() => {
    getSupplies(); // Load supplies data by default
});

// Modal states and refs
const showSupplyModal = ref(false);
const showCreateSupplierModal = ref(false);
const showViewItemsModal = ref(false);
const selectedSupply = ref({
    items: []
});
const isSubmitting = ref(false);
const processing = ref(false);
const supplierToDelete = ref(null);

const products = ref([]);

const searchResults = ref([]);
const isLoading = ref(false);

async function searchProduct(query) {
    isLoading.value = true;
    try {
        const response = await axios.post(route('products.search'), {
            query: query
        });
        searchResults.value = response.data;
    } catch (error) {
        console.error('Error searching products:', error);
    } finally {
        isLoading.value = false;
    }
};

// Form states
const supplyForm = ref({
    id: null,
    supplier_id: '',
    warehouse_id: '',
    invoice_number: '',
    supply_date: '',
    notes: '',
    items: [{
        id: null,
        product_id: '',
        product_name: '',
        quantity: '',
        batch_number: '',
        manufacturing_date: '',
        expiry_date: '',
        status: 'pending'
    }]
});

const form = ref({
    id: null,
    name: '',
    contact_person: '',
    email: '',
    phone: '',
    address: '',
    is_active: true,
    notes: '',
    errors: {}
});

// Refs for filters
const supplierFilters = ref({
    search: props.supplierFilters?.search || ''
});

const supplyFilters = ref({
    search: props.supplierFilters?.search || '',
    date_from: props.supplierFilters?.date_from || '',
    date_to: props.supplierFilters?.date_to || ''
});

// Selection states
const selectedSupplies = ref([]);

// Toggle all supplies selection
const toggleSelectAll = (event) => {
    if (event.target.checked) {
        // Select all supplies
        selectedSupplies.value = props.supplies.data.map(supply => supply.id);
    } else {
        // Deselect all supplies
        selectedSupplies.value = [];
    }
};

// Toggle individual supply selection
const toggleSupply = (supplyId) => {
    const index = selectedSupplies.value.indexOf(supplyId);
    if (index === -1) {
        selectedSupplies.value.push(supplyId);
    } else {
        selectedSupplies.value = selectedSupplies.value.filter(id => id !== supplyId);
    }
};

// Check if all supplies are selected
const isAllSelected = computed(() => {
    return props.supplies.data.length > 0 && selectedSupplies.value.length === props.supplies.data.length;
});

// Methods for supplies
const getSupplies = async () => {
    try {
        const response = await axios.get(route('supplies.index'), {
            params: {
                ...supplyFilters.value,
                tab: 'supplies'
            }
        });
        router.reload({ data: { supplies: response.data.supplies } });
    } catch (error) {
        console.error('Error fetching supplies:', error);
        toast.error('Failed to fetch supplies');
    }
};

// Methods for suppliers
const getSuppliers = () => {
    router.get(route('supplies.index'), {
        ...supplierFilters.value,
        tab: 'suppliers',
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Reset filters
const resetSupplyFilters = () => {
    supplyFilters.value = {
        search: '',
        date_from: '',
        date_to: '',
    };
    getSupplies();
};

const resetSupplierFilters = () => {
    supplierFilters.value = {
        search: '',
    };
    getSuppliers();
};

// Format date
const formatDate = (dateString) => {
    if (!dateString) return '—';
    return moment(dateString).format('LL');
};

// Format date to YYYY-MM-DD
const formatDateForInput = (date) => {
    if (!date) return '';
    return moment(date).format('YYYY-MM-DD');
};

// Open create supply modal
const openCreateSupplyModal = () => {
    resetForm();
    showSupplyModal.value = true;
};

// Close create supply modal
const closeSupplyModal = () => {
    showSupplyModal.value = false;
    resetForm();
};

// Reset supply form
const resetForm = () => {
    supplyForm.value = {
        id: null,
        supplier_id: '',
        warehouse_id: '',
        invoice_number: '',
        supply_date: '',
        notes: '',
        items: [{
            id: null,
            product_id: '',
            product_name: '',
            quantity: '',
            batch_number: '',
            manufacturing_date: '',
            expiry_date: '',
            status: 'pending'
        }]
    };
};

// Open edit supply modal
const openEditSupplyModal = (supply) => {
    resetForm();
    supplyForm.value = {
        id: supply.id,
        supplier_id: supply.supplier_id,
        warehouse_id: supply.warehouse_id,
        invoice_number: supply.invoice_number,
        supply_date: formatDateForInput(supply.supply_date),
        notes: supply.notes || '',
        items: supply.items?.map(item => ({
            id: item.id,
            product_id: {
                product_id: item.product_id,
                product_name: item.product_name
            },
            product_name: item.product_name,
            quantity: item.quantity,
            status: item.status,
            batch_number: item.batch_number || '',
            manufacturing_date: formatDateForInput(item.manufacturing_date),
            expiry_date: formatDateForInput(item.expiry_date),
            notes: item.notes || '',
        })) || []
    };

    showSupplyModal.value = true;
};

// Add product
const addProduct = () => {
    supplyForm.value.items.push({
        id: null,
        product_id: '',
        product_name: '',
        quantity: '',
        batch_number: '',
        manufacturing_date: '',
        expiry_date: '',
        status: 'pending'
    });
};

// Remove product
const removeProduct = (index) => {
    if (supplyForm.value.items[index].id && supplyForm.value.items[index].status !== 'pending') {
        return;
    }
    if(supplyForm.value.items.length === 1) {
        return;
    }
    supplyForm.value.items.splice(index, 1);
};

// Submit supply
async function submitSupply() {
    isSubmitting.value = true;

    // Format the data before sending
    const formData = {
        ...supplyForm.value,
        items: supplyForm.value.items.map(item => ({
            ...item,
            product_id: item.product_id?.product_id || item.product_id,
            product_name: item.product_id?.product_name || item.product_name
        }))
    };

    await axios.post(route('supplies.store'), formData)
        .then((response) => {
            toast.success(response.data);
            closeSupplyModal();
            getSupplies();
            isSubmitting.value = false;
        })
        .catch((error) => {
            console.log(error.response.data);
            isSubmitting.value = false;
            toast.error(error.response?.data);
        });
};

// Open create supplier modal
const openCreateSupplierModal = () => {
    resetSupplierForm();
    showCreateSupplierModal.value = true;
};

const closeCreateSupplierModal = () => {
    showCreateSupplierModal.value = false;
    resetSupplierForm();
};

const submitSupplierForm = async () => {
    isSubmitting.value = true;
    await axios.post(route('suppliers.store'), form.value)
        .then((response) => {
            isSubmitting.value = false;
            toast.success(response.data);
            closeCreateSupplierModal();
            getSuppliers();
        })
        .catch((error) => {
            isSubmitting.value = false;
            toast.error(error.response.data);
            console.log(error.response.data);
        });
};

// Open edit supplier modal
function openEditSupplierModal(supplier) {
    form.value = {
        id: supplier.id,
        name: supplier.name,
        contact_person: supplier.contact_person || '',
        email: supplier.email || '',
        phone: supplier.phone || '',
        address: supplier.address || '',
        is_active: supplier.is_active,
        notes: supplier.notes || '',
        errors: {},
    };
    showCreateSupplierModal.value = true;
};

// Reset supplier form
const resetSupplierForm = () => {
    form.value = {
        id: null,
        name: '',
        contact_person: '',
        email: '',
        phone: '',
        address: '',
        is_active: true,
        notes: '',
        errors: {},
    };
};

// Confirm delete supplier
const confirmDeleteSupplier = (supplier) => {
    Swal.fire({
        title: 'Are you sure?',
        text: 'You are about to delete this supplier. This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            supplierToDelete.value = supplier;
            deleteSupplier();
        }
    });
};

// Close supplier modal
const closeSupplierModal = () => {
    supplierToDelete.value = null;
};

// Delete supplier
const deleteSupplier = async () => {
    if (!supplierToDelete.value || isSubmitting.value) return;

    isSubmitting.value = true;

    await axios.delete(route('suppliers.destroy', supplierToDelete.value.id))
        .then((response) => {
            isSubmitting.value = false;
            toast.success(response.data);
            closeSupplierModal();
            getSuppliers();
        })
        .catch((error) => {
            isSubmitting.value = false;
            toast.error(error.response?.data);
        });
};

// Get supply items
const getSupplyItems = async (supplyId) => {
    try {
        const response = await axios.get(route('supplies.items', supplyId));
        return response.data;
    } catch (error) {
        console.error('Error fetching supply items:', error);
        toast.error('Failed to fetch supply items');
        return [];
    }
};

// Open view items modal
const openViewItemsModal = async (supply) => {
    try {
        const response = await axios.get(route('supplies.items', supply.id));
        selectedSupply.value = {
            ...supply,
            items: response.data
        };
        showViewItemsModal.value = true;
    } catch (error) {
        console.error('Error opening modal:', error);
        toast.error('Failed to load supply items');
        closeViewItemsModal();
    }
};

// Close view items modal
const closeViewItemsModal = () => {
    showViewItemsModal.value = false;
    selectedSupply.value = {
        items: []
    };
};

// Approve/Reject item
const approveItem = async (item) => {
    try {
        const response = await axios.patch(route('supplies.items.update-status', { item: item.id }), {
            status: 'approved'
        });

        // Update the item in the list
        const index = selectedSupply.value.items.findIndex(i => i.id === item.id);
        if (index !== -1) {
            selectedSupply.value.items[index].status = 'approved';
        }

        toast.success('Item status updated successfully');
    } catch (error) {
        console.error('Error updating item status:', error);
        toast.error(error.response?.data?.message || 'Failed to update item status');
    }
};

const rejectItem = async (item) => {
    try {
        const response = await axios.patch(route('supplies.items.update-status', { item: item.id }), {
            status: 'rejected'
        });

        // Update the item in the list
        const index = selectedSupply.value.items.findIndex(i => i.id === item.id);
        if (index !== -1) {
            selectedSupply.value.items[index].status = 'rejected';
        }

        toast.success('Item status updated successfully');
    } catch (error) {
        console.error('Error updating item status:', error);
        toast.error(error.response?.data?.message || 'Failed to update item status');
    }
};

// Select product
const selectProduct = (index, product) => {
    supplyForm.value.items[index].product_id = product;
    supplyForm.value.items[index].product_name = product.product_name;
}

const confirmBulkDelete = async () => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete ${selectedSupplies.value.length} supplies. This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete them!',
        showLoaderOnConfirm: true,
        timer: 10000,
        timerProgressBar: true,
        preConfirm: async () => {
            try {
                const response = await axios.post(route('supplies.bulk-delete'), {
                    ids: selectedSupplies.value
                });
                return response.data;
            } catch (error) {
                const message = error.response?.data?.message || 'Failed to delete supplies';
                Swal.showValidationMessage(message);
                throw error;
            }
        },
        allowOutsideClick: () => !Swal.isLoading()
    });

    if (result.isConfirmed) {
        await getSupplies();
        selectedSupplies.value = [];
        Swal.fire({
            title: 'Deleted!',
            text: `Successfully deleted ${result.value.deleted_count} supplies.`,
            icon: 'success',
            timer: 2000,
            timerProgressBar: true
        });
    }
};

const confirmBulkDeleteSuppliers = async () => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete ${selectedSuppliers.value.length} suppliers. This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete them!',
        showLoaderOnConfirm: true,
        timer: 10000,
        timerProgressBar: true,
        preConfirm: async () => {
            try {
                const response = await axios.post(route('suppliers.bulk-delete'), {
                    ids: selectedSuppliers.value
                });
                return response.data;
            } catch (error) {
                Swal.showValidationMessage(
                    error.response?.data?.message || 'Failed to delete suppliers'
                );
            }
        },
        allowOutsideClick: () => !Swal.isLoading()
    });

    if (result.isConfirmed) {
        await getSuppliers();
        selectedSuppliers.value = [];
        Swal.fire({
            title: 'Deleted!',
            text: 'The selected suppliers have been deleted.',
            icon: 'success',
            timer: 2000,
            timerProgressBar: true
        });
    }
};

watch(() => currentTab.value, (newTab) => {
    if (newTab === 'suppliers') {
        getSuppliers();
    } else {
        getSupplies();
    }
});
</script>