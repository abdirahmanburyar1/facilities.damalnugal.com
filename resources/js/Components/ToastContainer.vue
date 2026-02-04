<template>
    <div>
        <TransitionGroup name="toast">
            <div 
                v-for="toast in toasts" 
                :key="toast.id"
                :class="[
                    'fixed z-[9999] p-4 rounded-md shadow-lg transition-all duration-300 transform',
                    getPositionClasses(toast.position),
                    getTypeClasses(toast.type)
                ]"
                :style="getToastStyle(toast)"
            >
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <!-- Success Icon -->
                        <svg v-if="toast.type === 'success'" class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <!-- Error Icon -->
                        <svg v-else-if="toast.type === 'error'" class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <!-- Info Icon -->
                        <svg v-else class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium" :class="getTextColorClass(toast.type)">
                            {{ toast.message }}
                        </p>
                    </div>
                    <div class="ml-auto pl-3">
                        <div class="-mx-1.5 -my-1.5">
                            <button 
                                @click="removeToast(toast.id)"
                                class="inline-flex rounded-md p-1.5" 
                                :class="getCloseButtonClass(toast.type)"
                            >
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import ToastService from '@/Services/ToastService';

const toasts = computed(() => {
    return ToastService.getToasts();
});

const removeToast = (id) => {
    ToastService.remove(id);
};

const getPositionClasses = (position) => {
    switch (position) {
        case 'top-right':
            return 'top-4 right-4';
        case 'top-left':
            return 'top-4 left-4';
        case 'bottom-right':
            return 'bottom-4 right-4';
        case 'bottom-left':
            return 'bottom-4 left-4';
        case 'top-center':
            return 'top-4 left-1/2 -translate-x-1/2';
        case 'bottom-center':
            return 'bottom-4 left-1/2 -translate-x-1/2';
        default:
            return 'top-4 right-4';
    }
};

const getTypeClasses = (type) => {
    switch (type) {
        case 'success':
            return 'bg-green-50 border-l-4 border-green-400';
        case 'error':
            return 'bg-red-50 border-l-4 border-red-400';
        case 'info':
            return 'bg-blue-50 border-l-4 border-blue-400';
        default:
            return 'bg-blue-50 border-l-4 border-blue-400';
    }
};

const getTextColorClass = (type) => {
    switch (type) {
        case 'success':
            return 'text-green-800';
        case 'error':
            return 'text-red-800';
        case 'info':
            return 'text-blue-800';
        default:
            return 'text-blue-800';
    }
};

const getCloseButtonClass = (type) => {
    switch (type) {
        case 'success':
            return 'bg-green-50 text-green-500 hover:bg-green-100';
        case 'error':
            return 'bg-red-50 text-red-500 hover:bg-red-100';
        case 'info':
            return 'bg-blue-50 text-blue-500 hover:bg-blue-100';
        default:
            return 'bg-blue-50 text-blue-500 hover:bg-blue-100';
    }
};

// Calculate vertical offset for stacking toasts
const getToastStyle = (toast) => {
    const index = toasts.value.findIndex(t => t.id === toast.id);
    const offset = index * 80; // Adjust based on your toast height + margin
    
    if (toast.position.includes('top')) {
        return { top: `${offset + 16}px` };
    } else if (toast.position.includes('bottom')) {
        return { bottom: `${offset + 16}px` };
    }
    
    return {};
};
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateY(-30px);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>
