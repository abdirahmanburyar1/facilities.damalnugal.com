<script setup lang="ts">
import { ref, watch, computed } from 'vue';

const props = defineProps<{
  isOpen: boolean;
  title: string;
  actionType: 'liquidate' | 'dispose';
  item?: any;
}>();

const emit = defineEmits(['close', 'submit']);

const formData = ref({
  id: '',
  product_id: '',
  packing_list_id: '',
  purchase_order_id: '',
  quantity: 0,
  original_quantity: 0,
  barcode: '',
  expire_date: '',
  batch_number: '',
  uom: '',
  status: '',
  note: '',
  attachments: [] as File[]
});

const isSubmitting = ref(false);
const error = ref('');
const showAdvanced = ref(false);

// Reset form when modal opens with new item
watch(() => props.isOpen, (isOpen) => {
  if (isOpen && props.item) {
    formData.value = {
      id: props.item.id || '',
      product_id: props.item.product?.id || '',
      packing_list_id: props.item.packing_list?.id || '',
      purchase_order_id: props.item.purchase_order_id || '',
      quantity: props.item.quantity || 0,
      original_quantity: props.item.quantity || 0,
      barcode: props.item.packing_list?.barcode || '',
      expire_date: props.item.packing_list?.expire_date || '',
      batch_number: props.item.packing_list?.batch_number || '',
      uom: props.item.packing_list?.uom || '',
      status: props.item.status || '',
      note: '',
      attachments: []
    };
    error.value = '';
  }
});

const actionText = computed(() => {
  return props.actionType === 'liquidate' ? 'Liquidate' : 'Dispose';
});

const statusOptions = computed(() => {
  return props.actionType === 'liquidate' 
    ? ['damaged', 'expired', 'missing']
    : ['damaged', 'lost', 'expired', 'missing'];
});

const handleSubmit = async () => {
  if (isSubmitting.value) return;
  
  error.value = '';
  
  // Validate form
  if (!formData.value.status) {
    error.value = 'Please select a status';
    return;
  }
  
  if (formData.value.quantity <= 0) {
    error.value = 'Quantity must be greater than 0';
    return;
  }
  
  isSubmitting.value = true;
  
  try {
    // Create form data
    const submitData = new FormData();
    
    // Add all form fields
    submitData.append('id', formData.value.id);
    submitData.append('product_id', formData.value.product_id);
    submitData.append('packing_list_id', formData.value.packing_list_id);
    submitData.append('purchase_order_id', formData.value.purchase_order_id);
    submitData.append('quantity', formData.value.quantity);
    submitData.append('original_quantity', formData.value.original_quantity);
    submitData.append('barcode', formData.value.barcode);
    submitData.append('expire_date', formData.value.expire_date);
    submitData.append('batch_number', formData.value.batch_number);
    submitData.append('uom', formData.value.uom);
    submitData.append('status', formData.value.status);
    submitData.append('note', formData.value.note);
    
    // Append each attachment
    for (let i = 0; i < formData.value.attachments.length; i++) {
      submitData.append('attachments[]', formData.value.attachments[i]);
    }
    
    // Submit the form
    emit('submit', submitData);
  } catch (err) {
    console.error('Error submitting form:', err);
    error.value = 'An error occurred while submitting the form';
  } finally {
    isSubmitting.value = false;
  }
};

const closeModal = () => {
  emit('close');
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity" @click="closeModal">
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
      </div>
      
      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
              <h3 class="text-lg leading-6 font-medium text-gray-900">{{ title }}</h3>
              
              <div class="mt-4">
                <form @submit.prevent="handleSubmit" class="space-y-4">
                  <!-- Status Selection -->
                  <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select 
                      id="status" 
                      v-model="formData.status" 
                      class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                    >
                      <option value="" disabled>Select a status</option>
                      <option v-for="option in statusOptions" :key="option" :value="option">
                        {{ option.charAt(0).toUpperCase() + option.slice(1) }}
                      </option>
                    </select>
                  </div>
                  
                  <!-- Quantity -->
                  <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                    <input 
                      type="number" 
                      id="quantity" 
                      v-model="formData.quantity" 
                      min="1"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                  </div>
                  
                  <!-- Note -->
                  <div>
                    <label for="note" class="block text-sm font-medium text-gray-700">Note (Optional)</label>
                    <textarea 
                      id="note" 
                      v-model="formData.note" 
                      rows="3"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                      placeholder="Add any additional information here..."
                    ></textarea>
                  </div>
                  

                  
                  <!-- Error Message -->
                  <div v-if="error" class="text-red-500 text-sm mt-2">{{ error }}</div>
                </form>
              </div>
            </div>
          </div>
        </div>
        
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button 
            type="button" 
            @click="handleSubmit"
            :disabled="isSubmitting"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
          >
            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ actionText }}
          </button>
          <button 
            type="button" 
            @click="closeModal"
            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
