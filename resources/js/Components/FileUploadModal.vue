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
                <div class="space-y-4">
                  <!-- File Upload Area -->
                  <div class="border-2 border-dashed border-gray-300 rounded-md p-6 text-center hover:border-blue-500 transition-colors">
                    <input 
                      type="file" 
                      ref="fileInput"
                      multiple
                      accept="application/pdf"
                      class="hidden" 
                      @change="handleFileChange"
                    />
                    
                    <div @click="triggerFileInput" class="cursor-pointer">
                      <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                      </svg>
                      <p class="mt-1 text-sm text-gray-600">
                        <span class="font-medium text-blue-600 hover:text-blue-500">Click to upload</span> or drag and drop
                      </p>
                      <p class="mt-1 text-xs text-gray-500">PDF files only (max 5 files, 10MB each)</p>
                    </div>
                  </div>
                  
                  <!-- File List -->
                  <div v-if="files.length > 0" class="mt-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Selected Files:</h4>
                    <ul class="divide-y divide-gray-200 border rounded-md">
                      <li v-for="(file, index) in files" :key="index" class="px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center">
                          <svg class="h-5 w-5 text-gray-400 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                          </svg>
                          <span class="text-sm text-gray-700 truncate max-w-xs">{{ file.name }}</span>
                          <span class="ml-2 text-xs text-gray-500">{{ formatFileSize(file.size) }}</span>
                        </div>
                        <button @click="removeFile(index)" class="text-red-500 hover:text-red-700">
                          <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                        </button>
                      </li>
                    </ul>
                  </div>
                  
                  <!-- Error Message -->
                  <div v-if="error" class="text-red-500 text-sm mt-2">{{ error }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button 
            type="button" 
            @click="submitFiles"
            :disabled="isSubmitting"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
          >
            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Submit
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

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Upload Files'
  },
  maxFiles: {
    type: Number,
    default: 5
  },
  maxFileSize: {
    type: Number,
    default: 10 // in MB
  }
});

const emit = defineEmits(['close', 'submit']);

const fileInput = ref(null);
const files = ref([]);
const isSubmitting = ref(false);
const error = ref('');

// Reset files when modal opens/closes
watch(() => props.isOpen, (isOpen) => {
  if (!isOpen) {
    files.value = [];
    error.value = '';
  }
});

const triggerFileInput = () => {
  fileInput.value.click();
};

const handleFileChange = (event) => {
  const newFiles = Array.from(event.target.files);
  
  // Validate file count
  if (newFiles.length + files.value.length > props.maxFiles) {
    error.value = `You can only upload a maximum of ${props.maxFiles} files.`;
    return;
  }
  
  // Validate file type and size
  for (const file of newFiles) {
    if (file.type !== 'application/pdf') {
      error.value = 'Only PDF files are allowed.';
      return;
    }
    
    if (file.size > props.maxFileSize * 1024 * 1024) {
      error.value = `File size should not exceed ${props.maxFileSize}MB.`;
      return;
    }
  }
  
  error.value = '';
  files.value = [...files.value, ...newFiles];
  
  // Reset the input so the same file can be selected again if needed
  event.target.value = '';
};

const removeFile = (index) => {
  files.value.splice(index, 1);
};

const formatFileSize = (bytes) => {
  if (bytes < 1024) {
    return bytes + ' B';
  } else if (bytes < 1024 * 1024) {
    return (bytes / 1024).toFixed(1) + ' KB';
  } else {
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }
};

const submitFiles = () => {
  if (files.value.length === 0) {
    error.value = 'Please select at least one file to upload.';
    return;
  }
  
  isSubmitting.value = true;
  
  try {
    emit('submit', files.value);
  } catch (err) {
    error.value = 'An error occurred while submitting the files.';
    console.error('Error submitting files:', err);
  } finally {
    isSubmitting.value = false;
  }
};

const closeModal = () => {
  emit('close');
};
</script>
