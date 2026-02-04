<template>
    <div class="relative" ref="container">
        <div class="w-full border border-gray-300 rounded-md shadow-sm focus-within:border-indigo-500 focus-within:ring focus-within:ring-indigo-200 focus-within:ring-opacity-50">
            <!-- Selected options display -->
            <div class="flex flex-wrap p-2 gap-1">
                <div v-for="selectedId in modelValue" :key="selectedId" class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded-md flex items-center text-sm">
                    {{ getOptionLabel(selectedId) }}
                    <button @click="removeOption(selectedId)" type="button" class="ml-1 text-indigo-500 hover:text-indigo-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <div class="flex-grow flex items-center">
                    <input 
                        type="text" 
                        class="w-full outline-none border-none focus:ring-0 p-1 text-sm"
                        :placeholder="modelValue.length ? 'Add more...' : 'Select options...'"
                        v-model="searchTerm"
                        @focus="open"
                    />
                    <button 
                        @click="toggle" 
                        type="button" 
                        class="p-1 text-gray-400 hover:text-gray-600"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Dropdown -->
        <div 
            v-show="isOpen" 
            class="fixed z-[9999] w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
            :style="{
                position: 'absolute',
                top: dropdownPosition.top + 'px',
                left: dropdownPosition.left + 'px',
                width: dropdownPosition.width + 'px',
                maxHeight: '300px'
            }"
            ref="dropdown"
        >
            <div class="sticky top-0 bg-white border-b border-gray-200 p-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700">{{ filteredOptions.length }} options</span>
                    <div class="flex space-x-2">
                        <button 
                            @click="selectAll" 
                            type="button" 
                            class="text-xs px-2 py-1 bg-indigo-50 text-indigo-700 rounded hover:bg-indigo-100"
                        >
                            Select All
                        </button>
                        <button 
                            @click="clearAll" 
                            type="button" 
                            class="text-xs px-2 py-1 bg-gray-50 text-gray-700 rounded hover:bg-gray-100"
                        >
                            Clear All
                        </button>
                    </div>
                </div>
            </div>
            
            <div v-if="filteredOptions.length === 0" class="p-2 text-gray-500 text-sm">No options found</div>
            
            <div class="p-1">
                <div 
                    v-for="option in filteredOptions" 
                    :key="option.id" 
                    @click="toggleOption(option.id)"
                    class="p-2 hover:bg-gray-100 cursor-pointer flex items-center text-sm rounded"
                    :class="{ 'bg-gray-50': isSelected(option.id) }"
                >
                    <input 
                        type="checkbox" 
                        :checked="isSelected(option.id)" 
                        class="mr-2 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        @click.stop
                        @change="toggleOption(option.id)"
                    />
                    {{ option.name }}
                </div>
            </div>
        </div>
        
        <p v-if="error" class="text-red-500 text-xs mt-1">{{ error }}</p>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        required: true
    },
    options: {
        type: Array,
        required: true
    },
    placeholder: {
        type: String,
        default: 'Select options'
    },
    error: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue']);

const searchTerm = ref('');
const isOpen = ref(false);
const dropdown = ref(null);
const container = ref(null);
const dropdownPosition = ref({
    top: 0,
    left: 0,
    width: 0
});

// Calculate dropdown position
const calculateDropdownPosition = () => {
    if (!container.value) return;
    
    const rect = container.value.getBoundingClientRect();
    dropdownPosition.value = {
        top: rect.bottom + window.scrollY,
        left: rect.left + window.scrollX,
        width: rect.width
    };
};

const filteredOptions = computed(() => {
    if (!searchTerm.value) return props.options;
    return props.options.filter(option => 
        option.name.toLowerCase().includes(searchTerm.value.toLowerCase())
    );
});

const isSelected = (id) => {
    return props.modelValue.includes(id);
};

const toggleOption = (id) => {
    const newValue = [...props.modelValue];
    const index = newValue.indexOf(id);
    
    if (index === -1) {
        newValue.push(id);
    } else {
        newValue.splice(index, 1);
    }
    
    emit('update:modelValue', newValue);
};

const removeOption = (id) => {
    const newValue = props.modelValue.filter(item => item !== id);
    emit('update:modelValue', newValue);
};

const getOptionLabel = (id) => {
    const option = props.options.find(opt => opt.id === id);
    return option?.name ?? '';
};

const selectAll = () => {
    const allIds = filteredOptions.value.map(option => option.id);
    emit('update:modelValue', allIds);
};

const clearAll = () => {
    emit('update:modelValue', []);
};

const open = () => {
    isOpen.value = true;
    calculateDropdownPosition();
};

const close = () => {
    isOpen.value = false;
};

const toggle = () => {
    isOpen.value = !isOpen.value;
};

const handleClickOutside = (event) => {
    const element = event.target;
    if (!element.closest('.relative')) {
        close();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('scroll', calculateDropdownPosition);
    window.addEventListener('resize', calculateDropdownPosition);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('scroll', calculateDropdownPosition);
    window.removeEventListener('resize', calculateDropdownPosition);
});
</script>
