<template>
    <div class="min-h-screen bg-gray-50">
        <SplashScreen v-if="showSplash" @complete="onSplashComplete" />
        <div v-else class="flex flex-col min-h-screen">
            <!-- Top Navigation Bar -->
            <header class="bg-gradient-to-r from-blue-600 to-cyan-500 text-white shadow-md">
                <div class="container mx-auto px-4 py-3">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6h2m7-6h8m0 0h2m-2 6h2M5 15h2m0 0h2m-2 0v-2m0 2v2m6-10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span class="text-xl font-bold">PharmaStock</span>
                            </div>
                            <nav class="hidden md:flex space-x-1">
                                <a href="#" class="px-3 py-2 rounded-md text-sm font-medium bg-blue-700">Dashboard</a>
                                <a href="#" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors">Inventory</a>
                                <a href="#" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors">Orders</a>
                                <a href="#" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors">Reports</a>
                            </nav>
                        </div>
                        <div class="flex items-center space-x-4">
                            <button class="p-1 rounded-full text-white hover:bg-blue-700 focus:outline-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </button>
                            <div class="relative">
                                <button @click="toggleUserMenu" class="flex items-center space-x-2 focus:outline-none">
                                    <div class="h-8 w-8 rounded-full bg-white text-blue-600 flex items-center justify-center font-bold">
                                        {{ userInitials }}
                                    </div>
                                    <span class="hidden md:inline text-sm font-medium">{{ userName }}</span>
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div v-if="showUserMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</a>
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                    <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100" @click="logout">Sign out</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 container mx-auto px-4 py-6">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <component :is="app"></component>
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 py-4">
                <div class="container mx-auto px-4">
                    <div class="flex flex-col md:flex-row justify-between items-center text-sm text-gray-600">
                        <div class="mb-2 md:mb-0">
                            © {{ new Date().getFullYear() }} PharmaStock. All rights reserved.
                        </div>
                        <div class="flex space-x-4">
                            <a href="#" class="hover:text-blue-600">Privacy Policy</a>
                            <a href="#" class="hover:text-blue-600">Terms of Service</a>
                            <a href="#" class="hover:text-blue-600">Help Center</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import SplashScreen from '@/Components/SplashScreen.vue';

const props = defineProps({
    app: Object,
    auth: Object
});

const showSplash = ref(true);
const showUserMenu = ref(false);
const hasSeenSplash = computed(() => {
    return localStorage.getItem('hasSeenSplash') === 'true';
});

const userName = computed(() => {
    return props.auth?.user?.name || 'User';
});

const userInitials = computed(() => {
    return userName.value
        .split(' ')
        .map(name => name[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
});

const toggleUserMenu = () => {
    showUserMenu.value = !showUserMenu.value;
};

const closeUserMenu = (event) => {
    if (!event.target.closest('.relative')) {
        showUserMenu.value = false;
    }
};

const logout = () => {
    router.post(route('logout'));
};

onMounted(() => {
    // Skip splash screen if already seen in this session
    if (hasSeenSplash.value) {
        showSplash.value = false;
    }
    
    // Close user menu when clicking outside
    document.addEventListener('click', closeUserMenu);
});

onUnmounted(() => {
    document.removeEventListener('click', closeUserMenu);
});

const onSplashComplete = () => {
    showSplash.value = false;
    localStorage.setItem('hasSeenSplash', 'true');
    
    // Reset the splash screen after 30 minutes of inactivity
    const resetSplashAfterInactivity = () => {
        let inactivityTimer;
        
        const resetTimer = () => {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(() => {
                localStorage.removeItem('hasSeenSplash');
            }, 30 * 60 * 1000); // 30 minutes
        };
        
        // Reset timer on user activity
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(
            event => document.addEventListener(event, resetTimer, false)
        );
        
        resetTimer();
    };
    
    resetSplashAfterInactivity();
};
</script>

<style>
/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}
</style>
