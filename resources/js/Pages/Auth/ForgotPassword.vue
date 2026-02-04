<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const isLoading = ref(false);
const resetAnimation = ref(false);

const submit = () => {
    if (form.processing) return;
    
    isLoading.value = true;
    resetAnimation.value = true;
    
    form.post(route('password.email'), {
        onFinish: () => {
            isLoading.value = false;
            setTimeout(() => {
                resetAnimation.value = false;
            }, 500);
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <div class="reset-container">
            <div 
                class="reset-card" 
                :class="{ 'reset-animation': resetAnimation }"
            >
                <div class="reset-header">
                    <h1 class="text-2xl font-bold text-gray-800">Password Recovery</h1>
                    <p class="text-gray-600 text-sm mt-1">We'll help you reset your password</p>
                </div>
                
                <div class="reset-info">
                    Forgot your password? No problem. Just let us know your email
                    address and we will email you a password reset link that will allow
                    you to choose a new one.
                </div>

                <div
                    v-if="status"
                    class="status-message mb-4"
                >
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="reset-form">
                    <div class="form-group">
                        <InputLabel for="email" value="Email" class="label-enhanced" />

                        <div class="input-with-icon">
                            <i class="input-icon">✉️</i>
                            <TextInput
                                id="email"
                                type="email"
                                class="input-enhanced"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                                :disabled="isLoading"
                                placeholder="Enter your email address"
                            />
                        </div>

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="buttons-container">
                        <PrimaryButton
                            class="reset-button"
                            :class="{ 'btn-loading': isLoading }"
                            :disabled="form.processing || isLoading"
                        >
                            <span v-if="isLoading" class="loading-spinner">
                                <span class="spinner"></span>
                            </span>
                            <span v-else>Email Password Reset Link</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.reset-container {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 1.5rem;
}

.reset-card {
    width: 100%;
    max-width: 650px;
    background: white;
    border-radius: 12px;
    padding: 2.5rem;
    transition: all 0.3s ease;
    transform: translateY(0);
}

.reset-animation {
    animation: pulse 0.5s ease;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

.reset-header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.reset-info {
    text-align: center;
    color: #4a5568;
    margin-bottom: 1.5rem;
    line-height: 1.5;
    font-size: 0.95rem;
}

.status-message {
    background-color: #f0fff4;
    border-left: 4px solid #48bb78;
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-radius: 4px;
    color: #2f855a;
    font-weight: 500;
}

.reset-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    margin-bottom: 0.5rem;
}

.label-enhanced {
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 0.5rem;
    display: block;
}

.input-with-icon {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 12px;
    color: #a0aec0;
    font-size: 16px;
}

.input-enhanced {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem !important;
    border-radius: 8px !important;
    border: 1px solid #e2e8f0 !important;
    background-color: #f8fafc;
    transition: all 0.2s ease;
}

.input-enhanced:focus {
    border-color: #667eea !important;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15) !important;
    background-color: white;
}

.buttons-container {
    display: flex;
    flex-direction: column;
    margin-top: 1rem;
}

.reset-button {
    width: 100%;
    padding: 0.75rem !important;
    border-radius: 8px !important;
    font-weight: 600 !important;
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: all 0.3s ease !important;
    background: linear-gradient(to right, #667eea, #764ba2) !important;
    border: none !important;
}

.reset-button:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3) !important;
}

.btn-loading {
    background: #667eea !important;
}

.loading-spinner {
    display: flex;
    align-items: center;
    justify-content: center;
}

.spinner {
    width: 18px;
    height: 18px;
    border: 2px solid transparent;
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
