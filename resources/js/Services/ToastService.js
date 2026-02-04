import { ref, reactive } from 'vue';

const toasts = reactive([]);
let toastId = 0;

export default {
    // Add a new toast notification
    add(message, options = {}) {
        const id = ++toastId;
        const toast = {
            id,
            message,
            type: options.type || 'info',
            position: options.position || 'top-right',
            duration: options.duration || 3000,
            autoClose: options.autoClose !== undefined ? options.autoClose : true,
        };
        
        toasts.push(toast);
        
        if (toast.autoClose) {
            setTimeout(() => {
                this.remove(id);
            }, toast.duration);
        }
        
        return id;
    },
    
    // Remove a toast by ID
    remove(id) {
        const index = toasts.findIndex(toast => toast.id === id);
        if (index !== -1) {
            toasts.splice(index, 1);
        }
    },
    
    // Clear all toasts
    clear() {
        toasts.splice(0, toasts.length);
    },
    
    // Success toast shorthand
    success(message, options = {}) {
        return this.add(message, { ...options, type: 'success' });
    },
    
    // Error toast shorthand
    error(message, options = {}) {
        return this.add(message, { ...options, type: 'error' });
    },
    
    // Info toast shorthand
    info(message, options = {}) {
        return this.add(message, { ...options, type: 'info' });
    },
    
    // Get all active toasts
    getToasts() {
        return toasts;
    }
};
