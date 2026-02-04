    <template>
    <Head title="Reports" />
    <AuthenticatedLayout title="Reports Dashboard" description="Comprehensive warehouse analytics and insights"
        img="/assets/images/report.png">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Reports Dashboard
                </h2>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-600">
                        Choose from available reports below
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6">
            <!-- Reports Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- Monthly Inventory Report -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-semibold text-gray-900">Monthly Inventory Report</h3>
                                <p class="text-sm text-gray-600">Logistic Data Hospitals & HCs (MF-11A)</p>
                            </div>
                        </div>
                        
                        <p class="text-gray-600 text-sm mb-4">
                            Generate comprehensive monthly inventory reports showing stock movements, balances, and stockout days for pharmaceutical facilities.
                        </p>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Monthly</span>
                            </div>
                            <button
                                @click="goToMonthlyInventory"
                                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                            >
                                Generate Report
                            </button>
                        </div>
                    </div>
                </div>


                
                <!-- Transfers Report -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-semibold text-gray-900">Transfers Report</h3>
                                <p class="text-sm text-gray-600">Stock Transfer Tracking</p>
                            </div>
                        </div>
                        
                        <p class="text-gray-600 text-sm mb-4">
                            Track all stock transfers between facilities and warehouses including status updates, transfer history, and summary statistics.
                        </p>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <span>Real-time</span>
                            </div>
                            <button
                                @click="goToTransfers"
                                class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-colors"
                            >
                                View Transfers
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Order Report -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-semibold text-gray-900">Order Report</h3>
                                <p class="text-sm text-gray-600">Order History & Status</p>
                            </div>
                        </div>
                        
                        <p class="text-gray-600 text-sm mb-4">
                            Track order history, delivery performance, and fulfillment rates across all facilities.
                        </p>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 002 2z"></path>
                                </svg>
                                <span>Historical Data</span>
                            </div>
                            <button
                                @click="goToOrders"
                                class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg transition-colors"
                            >
                                View Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Inventory Movements Report -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-semibold text-gray-900">Inventory Movements</h3>
                                <p class="text-sm text-gray-600">Stock Movement Tracking</p>
                            </div>
                        </div>
                        
                        <p class="text-gray-600 text-sm mb-4">
                            Track all inventory movements including transfers, orders, and dispenses with detailed transaction history.
                        </p>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span>Real-time</span>
                            </div>
                            <button
                                @click="goToInventoryMovements"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
                            >
                                View Report
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const goToMonthlyInventory = () => {
    try {
        router.get(route('reports.monthly-reports.index'))
    } catch (error) {
        console.error('Error navigating to monthly inventory:', error)
    }
}



const goToTransfers = () => {
    try {
        router.get('/reports/transfers')
    } catch (error) {
        console.error('Error navigating to transfers:', error)
    }
}

const goToOrders = () => {
    try {
        router.get('/reports/orders')
    } catch (error) {
        console.error('Error navigating to orders:', error)
    }
}

const goToInventoryMovements = () => {
    try {
        router.get(route('reports.facility-inventory-movements.index'))
    } catch (error) {
        console.error('Error navigating to inventory movements:', error)
    }
}
</script>

<style scoped>
/* Custom animations */
.report-card {
    transition: all 0.2s ease-in-out;
}

.report-card:hover {
    transform: translateY(-2px);
}

/* Button hover states */
button:not(:disabled):hover {
    transform: translateY(-1px);
}

button:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}
</style>
