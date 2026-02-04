<template>
    <Head title="Welcome - Warehouse Management System" />
    <div class="min-h-screen bg-gradient-to-br from-green-500 via-teal-400 to-blue-500 p-0 flex flex-col justify-center">
        <div class="container mx-auto px-2">
            <!-- Main Content -->
            <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
                <!-- Left Content -->
                <div class="flex-1 text-white">
                    <!-- Logos -->
                    <div class="flex gap-4 mb-6">
                        <img src="/assets/images/vista.png" alt="Vista Logo" class="h-[90px]" />
                        <img src="/assets/images/moh.png" alt="MOH Logo" class="h-[90px] rounded-full" />
                        <img src="/assets/images/psi.jpg" alt="PSI Logo" class="h-[90px] rounded" />
                    </div>

                    <!-- Main Text -->
                    <h1 class="text-2xl font-bold mb-4 leading-tight">
                        Ensuring Every<br />
                        Essential Medicines<br />
                        are Within Reach
                    </h1>

                    <p class="text-sm mb-6 max-w-md">
                        Step into the future of inventory management where every supply is tracked, every need met, and reliability is just a click away.
                    </p>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-4">
                        <Link 
                            v-if="$page.props.auth.user" 
                            :href="route('dashboard')" 
                            class="flex items-center gap-2 bg-gradient-to-r from-[#032B44] to-[#009E54] text-white px-8 py-4 rounded-full font-semibold hover:from-[#021331] hover:to-[#007A3F] transition-all shadow-lg"
                        >
                            <span>Home</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                            </svg>
                        </Link>
                        <Link 
                            v-else 
                            :href="route('login')" 
                            class="flex items-center gap-2 bg-gradient-to-r from-[#032B44] to-[#009E54] text-white px-8 py-4 rounded-full font-semibold hover:from-[#021331] hover:to-[#007A3F] transition-all shadow-lg"
                        >
                            <span>Sign in</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </Link>
                    </div>
                </div>

                <!-- Right Content -->
                <div class="flex-1 flex items-center justify-center">
                    <div class="relative">
                        <img src="/assets/images/10873037.webp" alt="Inventory Boxes" class="w-full max-w-xl mx-auto drop-shadow-2xl" />
                    </div>
                </div>
            </div>

        </div>
        <!-- Navigation Icons -->
        <div class="mt-2 relative overflow-hidden pb-2">
            <div class="flex whitespace-nowrap animate-scroll">
                <!-- First set of items -->
                <div class="flex gap-6">
                    <div v-for="(item, index) in navigationItems" :key="'first-'+index"
                        class="flex items-center gap-3 px-6 py-3 bg-white bg-opacity-20 backdrop-blur-lg rounded-full text-white hover:bg-opacity-30 transition-all cursor-pointer">
                        <div class="w-8 h-8 flex items-center justify-center mt-2">
                            <img :src="item.icon" :alt="item.text" class="w-6 h-6" />
                        </div>
                        <span class="text-sm font-medium">{{ item.text }}</span>
                        <div v-if="item.badge" class="w-5 h-5 flex items-center justify-center bg-red-500 rounded-full text-xs">
                            {{ item.badge }}
                        </div>
                    </div>
                </div>
                <!-- Duplicate set for seamless loop -->
                <div class="flex gap-6">
                    <div v-for="(item, index) in navigationItems" :key="'second-'+index"
                        class="flex items-center gap-3 px-6 py-3 bg-white bg-opacity-20 backdrop-blur-lg rounded-full text-white hover:bg-opacity-30 transition-all cursor-pointer">
                        <div class="w-8 h-8 flex items-center justify-center">
                            <img :src="item.icon" :alt="item.text" class="w-6 h-6" />
                        </div>
                        <span class="text-sm font-medium">{{ item.text }}</span>
                        <div v-if="item.badge" class="w-5 h-5 flex items-center justify-center bg-red-500 rounded-full text-xs">
                            {{ item.badge }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';

const navigationItems = [
    { text: 'Transfers', icon: '/assets/images/track.png' },
    { text: 'Dispences', icon: '/assets/images/track.png' },
    { text: 'Inventory', icon: '/assets/images/inventory.png' },
    { text: 'Facilities', icon: '/assets/images/dashboard.png' },
    { text: 'Reports', icon: '/assets/images/report.png' },
    { text: 'Orders', icon: '/assets/images/packages.png', badge: '9' }
];
</script>

<style scoped>
.backdrop-blur-lg {
    backdrop-filter: blur(8px);
}

@keyframes scroll {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}

.animate-scroll {
    animation: scroll 30s linear infinite;
}

.animate-scroll:hover {
    animation-play-state: paused;
}
</style>
