<script setup lang="ts">
import { ref, computed } from 'vue';

const props = defineProps<{
  label?: string;
  accept?: string;
  multiple?: boolean;
  maxFiles?: number;
  maxSize?: number; // in MB
  modelValue?: File[];
}>();

const emit = defineEmits(['update:modelValue']);

const files = ref<File[]>([]);
const dragActive = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

// Computed properties
const acceptedFileTypes = computed(() => props.accept || 'application/pdf');
const isMultiple = computed(() => props.multiple !== false);
const maxFileCount = computed(() => props.maxFiles || 5);
const maxFileSize = computed(() => (props.maxSize || 10) * 1024 * 1024); // Convert MB to bytes

// Methods
const handleFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.files) {
    addFiles(Array.from(input.files));
  }
};

const addFiles = (newFiles: File[]) => {
  // Filter files by type
  const validFiles = newFiles.filter(file => {
    if (!file.type.match(acceptedFileTypes.value)) {
      alert(`File type not allowed: ${file.type}. Only PDF files are accepted.`);
      return false;
    }
    
    if (file.size > maxFileSize.value) {
      alert(`File too large: ${file.name}. Maximum size is ${props.maxSize || 10}MB.`);
      return false;
    }
    
    return true;
  });
  
  // Check if adding these files would exceed the maximum
  if (files.value.length + validFiles.length > maxFileCount.value) {
    alert(`You can only upload a maximum of ${maxFileCount.value} files.`);
    return;
  }
  
  // Add valid files to our list
  const updatedFiles = [...files.value, ...validFiles];
  files.value = updatedFiles;
  emit('update:modelValue', updatedFiles);
};

const removeFile = (index: number) => {
  const updatedFiles = [...files.value];
  updatedFiles.splice(index, 1);
  files.value = updatedFiles;
  emit('update:modelValue', updatedFiles);
};

const handleDragEnter = (e: DragEvent) => {
  e.preventDefault();
  e.stopPropagation();
  dragActive.value = true;
};

const handleDragLeave = (e: DragEvent) => {
  e.preventDefault();
  e.stopPropagation();
  dragActive.value = false;
};

const handleDragOver = (e: DragEvent) => {
  e.preventDefault();
  e.stopPropagation();
  dragActive.value = true;
};

const handleDrop = (e: DragEvent) => {
  e.preventDefault();
  e.stopPropagation();
  dragActive.value = false;
  
  if (e.dataTransfer?.files) {
    addFiles(Array.from(e.dataTransfer.files));
  }
};

const triggerFileInput = () => {
  fileInput.value?.click();
};
</script>

<template>
  <div class="w-full">
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">{{ label }}</label>
    
    <!-- Drag & Drop Area -->
    <div
      @dragenter="handleDragEnter"
      @dragleave="handleDragLeave"
      @dragover="handleDragOver"
      @drop="handleDrop"
      @click="triggerFileInput"
      :class="[
        'border-2 border-dashed rounded-md p-4 text-center cursor-pointer transition-colors',
        dragActive ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-blue-400'
      ]"
    >
      <input
        ref="fileInput"
        type="file"
        :accept="acceptedFileTypes"
        :multiple="isMultiple"
        class="hidden"
        @change="handleFileChange"
      />
      
      <div class="flex flex-col items-center justify-center py-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <p class="mt-2 text-sm text-gray-600">
          Drag and drop PDF files here, or click to select files
        </p>
        <p class="mt-1 text-xs text-gray-500">
          Only PDF files are allowed (max {{ maxFileCount }} files, {{ props.maxSize || 10 }}MB each)
        </p>
      </div>
    </div>
    
    <!-- File List -->
    <div v-if="files.length > 0" class="mt-4">
      <h4 class="text-sm font-medium text-gray-700 mb-2">Selected Files:</h4>
      <ul class="space-y-2">
        <li v-for="(file, index) in files" :key="index" class="flex items-center justify-between p-2 bg-gray-50 rounded-md">
          <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <span class="text-sm truncate max-w-xs">{{ file.name }}</span>
            <span class="text-xs text-gray-500 ml-2">({{ (file.size / 1024 / 1024).toFixed(2) }} MB)</span>
          </div>
          <button 
            @click.stop="removeFile(index)" 
            class="text-red-500 hover:text-red-700 focus:outline-none"
            title="Remove file"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>
