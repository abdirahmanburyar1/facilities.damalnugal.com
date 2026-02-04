<template>
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gray-100 px-4 py-3 border-b">
            <h3 class="font-medium text-gray-700">Inventory Status</h3>
        </div>
        <div class="p-4 space-y-4">
            <div class="flex items-center">
                <div class="w-12 h-12 mr-3" ref="inStockAnimation"></div>
                <div class="flex-1 flex justify-between items-center">
                    <span class="text-sm font-medium">In Stock</span>
                    <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                        {{ statusCounts.find(s => s.status === 'in_stock')?.count || 0 }}
                    </span>
                </div>
            </div>
            
            <div class="flex items-center">
                <div class="w-12 h-12 mr-3" ref="lowStockAnimation"></div>
                <div class="flex-1 flex justify-between items-center">
                    <span class="text-sm font-medium">Low Stock</span>
                    <span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                        {{ statusCounts.find(s => s.status === 'low_stock')?.count || 0 }}
                    </span>
                </div>
            </div>
            
            <div class="flex items-center">
                <div class="w-12 h-12 mr-3" ref="outOfStockAnimation"></div>
                <div class="flex-1 flex justify-between items-center">
                    <span class="text-sm font-medium">Out of Stock</span>
                    <span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                        {{ statusCounts.find(s => s.status === 'out_of_stock')?.count || 0 }}
                    </span>
                </div>
            </div>
            
            <div class="flex items-center">
                <div class="w-12 h-12 mr-3" ref="soonExpiringAnimation"></div>
                <div class="flex-1 flex justify-between items-center">
                    <span class="text-sm font-medium">Soon Expiring</span>
                    <span class="bg-orange-100 text-orange-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                        {{ statusCounts.find(s => s.status === 'soon_expiring')?.count || 0 }}
                    </span>
                </div>
            </div>
            
            <div class="flex items-center">
                <div class="w-12 h-12 mr-3" ref="expiredAnimation"></div>
                <div class="flex-1 flex justify-between items-center">
                    <span class="text-sm font-medium">Expired</span>
                    <span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                        {{ statusCounts.find(s => s.status === 'expired')?.count || 0 }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import lottie from 'lottie-web';

const props = defineProps({
    statusCounts: {
        type: Array,
        required: true
    }
});

// References to animation containers
const inStockAnimation = ref(null);
const lowStockAnimation = ref(null);
const outOfStockAnimation = ref(null);
const soonExpiringAnimation = ref(null);
const expiredAnimation = ref(null);

// Animation data URLs
const inStockAnimationURL = 'https://assets5.lottiefiles.com/packages/lf20_ysrn2iwp.json'; // Checkmark animation
const lowStockAnimationURL = 'https://assets10.lottiefiles.com/packages/lf20_qjosmr4w.json'; // Warning animation
const outOfStockAnimationURL = 'https://assets7.lottiefiles.com/packages/lf20_ydo1amjm.json'; // Empty box animation
const soonExpiringAnimationURL = 'https://assets3.lottiefiles.com/packages/lf20_kqfglvmb.json'; // Clock animation
const expiredAnimationURL = 'https://assets2.lottiefiles.com/packages/lf20_yzoqyyqf.json'; // X mark animation

onMounted(() => {
    // Initialize all animations
    if (inStockAnimation.value) {
        lottie.loadAnimation({
            container: inStockAnimation.value,
            renderer: 'svg',
            loop: true,
            autoplay: true,
            path: inStockAnimationURL
        });
    }
    
    if (lowStockAnimation.value) {
        lottie.loadAnimation({
            container: lowStockAnimation.value,
            renderer: 'svg',
            loop: true,
            autoplay: true,
            path: lowStockAnimationURL
        });
    }
    
    if (outOfStockAnimation.value) {
        lottie.loadAnimation({
            container: outOfStockAnimation.value,
            renderer: 'svg',
            loop: true,
            autoplay: true,
            path: outOfStockAnimationURL
        });
    }
    
    if (soonExpiringAnimation.value) {
        lottie.loadAnimation({
            container: soonExpiringAnimation.value,
            renderer: 'svg',
            loop: true,
            autoplay: true,
            path: soonExpiringAnimationURL
        });
    }
    
    if (expiredAnimation.value) {
        lottie.loadAnimation({
            container: expiredAnimation.value,
            renderer: 'svg',
            loop: true,
            autoplay: true,
            path: expiredAnimationURL
        });
    }
});
</script>
