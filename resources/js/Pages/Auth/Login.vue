<script setup>
import { ref } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const isLoading = ref(false);
const showPassword = ref(false);
const loginAnimation = ref(false);

const submit = () => {
    if (form.processing) return;
    
    isLoading.value = true;
    loginAnimation.value = true;
    
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
            isLoading.value = false;
            setTimeout(() => {
                loginAnimation.value = false;
            }, 500);
        },
    });
};

// Toggle password visibility
const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div class="login-container">
            <div 
                class="login-card" 
                :class="{ 'login-animation': loginAnimation }"
            >
                <div class="login-header">
                    <h1 class="text-2xl font-bold text-gray-800">Sign In</h1>
                    <p class="text-gray-600 text-sm">Let's get you Dive in to VISTA</p>
                </div>
                
                <div v-if="status" class="status-message">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="login-form">
                    <div class="form-group">
                        <InputLabel for="username" value="Username" class="label-enhanced" />

                        <div class="input-with-icon">
                            <i class="fa-user input-icon">👤</i>
                            <TextInput
                                id="username"
                                type="text"
                                class="input-enhanced"
                                v-model="form.username"
                                required
                                autofocus
                                autocomplete="username"
                                :disabled="isLoading"
                                placeholder="Enter your username"
                            />
                        </div>
                        <InputError class="mt-2" :message="form.errors.username" />
                    </div>

                    <div class="form-group">
                        <InputLabel for="password" value="Password" class="label-enhanced" />

                        <div class="input-with-icon">
                            <i class="fa-lock input-icon">🔒</i>
                            <TextInput
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="input-enhanced"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                :disabled="isLoading"
                                placeholder="Enter your password"
                            />
                            <button type="button" class="password-toggle" @click="togglePasswordVisibility">
                                <span v-if="showPassword">👁️</span>
                                <span v-else>👁️‍🗨️</span>
                            </button>
                        </div>
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="remember-me">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" :disabled="isLoading" />
                            <span class="ms-2 text-sm text-gray-600">Remember me</span>
                        </label>
                    </div>

                    <div class="buttons-container">
                        <PrimaryButton
                            class="login-button"
                            :class="{ 'btn-loading': isLoading }"
                            :disabled="form.processing || isLoading"
                        >
                            <span v-if="isLoading" class="loading-spinner">
                                <span class="spinner"></span>
                            </span>
                            <span v-else>Log in</span>
                        </PrimaryButton>
                    </div>
                    
                    <div class="forgot-password">
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="reset-link"
                        >
                            Forgot your password?
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    /* min-height: 100%; */
    /* padding: 1.5rem; */
}

.login-card {
    max-width: 400px;
    background: white;
    padding: 2.5rem;
    transition: all 0.3s ease;
    transform: translateY(0);
}


.login-animation {
    animation: pulse 0.5s ease;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

.login-header {
    text-align: center;
    margin-bottom: 1rem;
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

.login-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
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

.password-toggle {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    cursor: pointer;
    color: #a0aec0;
    transition: color 0.2s ease;
}

.password-toggle:hover {
    color: #667eea;
}

.remember-me {
    display: flex;
    align-items: center;
    margin-top: 0.5rem;
}

.buttons-container {
    display: flex;
    flex-direction: column;
    margin-top: 1rem;
}

.login-button {
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

.login-button:hover:not(:disabled) {
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

.forgot-password {
    text-align: center;
    margin-top: 0.5rem;
}

.reset-link {
    color: #667eea;
    font-size: 0.875rem;
    text-decoration: none;
    transition: color 0.2s;
}

.reset-link:hover {
    color: #5a67d8;
    text-decoration: underline;
}
</style>
