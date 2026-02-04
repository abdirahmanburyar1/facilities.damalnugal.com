<template>
    <div class="w-64 ml-4">
        <div class="bg-white rounded-lg shadow-lg p-4">
            <h3 class="font-medium text-gray-700 mb-4">Inventory Status</h3>
            
            <!-- In Stock -->
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-full bg-emerald-500 flex items-center justify-center mr-3 relative">
                    <img src="/assets/images/in_stock.png" alt="In Stock" class="w-8 h-8">
                </div>
                <div>
                    <div class="text-lg font-bold text-gray-800">
                        {{ statusCounts.find(s => s.status === 'in_stock')?.count || 0 }}
                    </div>
                    <div class="text-sm text-gray-600">In Stock</div>
                </div>
            </div>
            
            <!-- Low Stock -->
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-full bg-orange-500 flex items-center justify-center mr-3">
                    <img src="/assets/images/low_stock.png" alt="Low Stock" class="w-8 h-8">
                </div>
                <div>
                    <div class="text-lg font-bold text-gray-800">
                        {{ statusCounts.find(s => s.status === 'low_stock')?.count || 0 }}
                    </div>
                    <div class="text-sm text-gray-600">Low Stock</div>
                </div>
            </div>
            
            <!-- Out of Stock -->
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-full bg-red-500 flex items-center justify-center mr-3">
                    <img src="/assets/images/out_of_stock.png" alt="Out of Stock" class="w-8 h-8">
                </div>
                <div>
                    <div class="text-lg font-bold text-gray-800">
                        {{ statusCounts.find(s => s.status === 'out_of_stock')?.count || 0 }}
                    </div>
                    <div class="text-sm text-gray-600">Out of Stock</div>
                </div>
            </div>
            
            <!-- Expired Stock -->
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-gray-500 flex items-center justify-center mr-3">
                    <img src="/assets/images/expired_stock.png" alt="Expired Stock" class="w-8 h-8">
                </div>
                <div>
                    <div class="text-lg font-bold text-gray-800">
                        {{ statusCounts.find(s => s.status === 'expired')?.count || 0 }}
                    </div>
                    <div class="text-sm text-gray-600">Expired Stock</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    statusCounts: {
        type: Array,
        required: true
    }
});
</script>
