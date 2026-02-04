<template>
    <div class="bg-white rounded-lg p-6">
        
        <!-- In Stock -->
        <div class="flex items-center mb-6">
            <div class="w-16 h-16 flex items-center justify-center mr-4">
                <img src="/assets/images/in_stock.png" alt="In Stock">
            </div>
            <div>
                <div class="text-xl font-bold text-gray-800">
                    {{ currentStatusCounts.in_stock }}
                </div>
                <div class="text-base text-gray-600">In Stock</div>
            </div>
        </div>
        
        <!-- Low Stock -->
        <div class="flex items-center mb-6">
            <div class="w-16 h-16 flex items-center justify-center mr-4">
                <img src="/assets/images/low_stock.png" alt="Low Stock">
            </div>
            <div>
                <div class="text-xl font-bold text-gray-800">
                    {{ currentStatusCounts.low_stock }}
                </div>
                <div class="text-base text-gray-600">Low Stock</div>
            </div>
           
        </div>
        
        <!-- Out of Stock -->
        <div class="flex items-center mb-6">
            <div class="w-16 h-16 flex items-center justify-center mr-4">
                <img src="/assets/images/out_stock.png" alt="Out of Stock">
            </div>
            <div>
                <div class="text-xl font-bold text-gray-800">
                    {{ currentStatusCounts.out_of_stock }}
                </div>
                <div class="text-base text-gray-600">Out of Stock</div>
            </div>
        </div>
        
        <!-- Expired Stock -->
        <div class="flex items-center">
            <div class="w-16 h-16 flex items-center justify-center mr-4">
                <img src="/assets/images/expired_stock.png" alt="Expired Stock">
            </div>
            <div>
                <div class="text-xl font-bold text-red-800">
                    {{ currentStatusCounts.expired }}
                </div>
                <div class="text-base text-gray-600">Expired Stock</div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';

const props = defineProps({
    statusCounts: {
        type: Array,
        required: true
    }
});

// Convert array to object for easier access
const statusCountsObj = computed(() => {
    const result = {
        in_stock: 0,
        low_stock: 0,
        out_of_stock: 0,
        expired: 0
    };
    
    props.statusCounts.forEach(item => {
        result[item.status] = item.count;
    });
    
    return result;
});

// Create reactive copy that will be updated in real-time
const currentStatusCounts = ref({...statusCountsObj.value});

// Listen to inventory changes
onMounted(() => {
    window.Echo.channel('inventory-updates')
        .listen('InventoryUpdated', (event) => {
            // Update counts based on the action and inventory status
            if (event.action === 'created') {
                // Determine which counter to increment based on inventory data
                if (event.quantity <= 0) {
                    currentStatusCounts.value.out_of_stock++;
                } else if (event.quantity <= event.reorder_level) {
                    currentStatusCounts.value.low_stock++;
                } else {
                    currentStatusCounts.value.in_stock++;
                }
                
                // Check if expired
                if (new Date(event.expiry_date) < new Date()) {
                    currentStatusCounts.value.expired++;
                }
            } else if (event.action === 'deleted') {
                // We don't know the previous state, so we'll refresh the page
                // after a short delay to ensure counts are correct
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
            // For updates, it's hard to determine what changed without knowing
            // the previous state, so we won't update counts for 'updated' events
        });
});

onBeforeUnmount(() => {
    window.Echo.leaveChannel('inventory-updates');
});
</script>
