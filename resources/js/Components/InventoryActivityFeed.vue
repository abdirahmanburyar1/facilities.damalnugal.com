<template>
    <div class="bg-white rounded-lg p-4 shadow">
        <h3 class="text-lg font-medium text-gray-900 mb-3">Inventory Activity</h3>
        
        <div v-if="activities.length === 0" class="text-center py-4 text-gray-500">
            No recent activity
        </div>
        
        <ul v-else class="space-y-3">
            <li v-for="(activity, index) in activities" :key="index" class="border-l-4 pl-3 py-2" 
                :class="{
                    'border-green-500 bg-green-50': activity.action === 'created',
                    'border-blue-500 bg-blue-50': activity.action === 'updated',
                    'border-red-500 bg-red-50': activity.action === 'deleted'
                }">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="font-semibold">{{ activity.product_name }}</span>
                        <span class="px-2 py-0.5 ml-2 text-xs rounded-full" 
                            :class="{
                                'bg-green-100 text-green-800': activity.action === 'created',
                                'bg-blue-100 text-blue-800': activity.action === 'updated',
                                'bg-red-100 text-red-800': activity.action === 'deleted'
                            }">
                            {{ formatAction(activity.action) }}
                        </span>
                    </div>
                    <span class="text-xs text-gray-500">{{ formatTime(activity.timestamp) }}</span>
                </div>
                <div class="text-sm text-gray-600 mt-1">
                    <span v-if="activity.action !== 'deleted'">
                        Qty: {{ activity.quantity }} @ {{ activity.warehouse_name }}
                    </span>
                    <span v-else>
                        Item has been removed
                    </span>
                </div>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const activities = ref([]);
const maxActivities = 10;

onMounted(() => {
    // Listen for inventory updates
    window.Echo.channel('inventory-updates')
        .listen('InventoryUpdated', (event) => {
            // Add new activity to the top of the list
            activities.value.unshift(event);
            
            // Keep only the latest activities
            if (activities.value.length > maxActivities) {
                activities.value.pop();
            }
        });
});

onBeforeUnmount(() => {
    window.Echo.leaveChannel('inventory-updates');
});

function formatAction(action) {
    switch (action) {
        case 'created': return 'Added';
        case 'updated': return 'Updated';
        case 'deleted': return 'Removed';
        default: return action;
    }
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
</script> 