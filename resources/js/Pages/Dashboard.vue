<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";
import "@/Components/multiselect.css";
import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import { Bar, Doughnut, Line, Pie } from 'vue-chartjs';
import dayjs from 'dayjs';
import axios from 'axios';
import Datepicker from 'vue-datepicker-next';
import 'vue-datepicker-next/index.css';

// Register the datalabels plugin
Chart.register(ChartDataLabels);

const props = defineProps({
    dashboardData: {
        type: Object,
        required: true,
        default: () => ({ summary: [] })
    },

    productCategoryCard: {
        type: Object,
        required: true,
        default: () => ({ Drugs: 0, Consumable: 0, Lab: 0 })
    },
    transferReceivedCard: {
        type: Number,
        required: true,
        default: 0
    },

    orderStats: {
        type: Object,
        required: true,
        default: () => ({
            pending: 0, reviewed: 0, approved: 0, in_process: 0, dispatched: 0, delivered: 0, received: 0, rejected: 0
        })
    },

    ordersDelayedCount: {
        type: Number,
        required: true,
        default: 0
    },

    inventoryStatusCounts: {
        type: Array,
        required: false,
        default: () => []
    },
    expiredStats: {
        type: Object,
        required: false,
        default: () => ({
            expired: 0,
            expiring_within_6_months: 0,
            expiring_within_1_year: 0
        })
    },
});

function getCount(abbr) {
    const found = props.dashboardData.summary.find(item => item.label === abbr);
    return found ? found.value : 0;
}

// Date filters
const dateRange = ref([
    dayjs().startOf('month').toDate(),
    dayjs().endOf('month').toDate()
]);

// Date presets for the datepicker
const datePresets = [
    { label: 'Today', value: [new Date(), new Date()] },
    { label: 'Yesterday', value: [dayjs().subtract(1, 'day').toDate(), dayjs().subtract(1, 'day').toDate()] },
    { label: 'Last 7 days', value: [dayjs().subtract(7, 'day').toDate(), new Date()] },
    { label: 'Last 30 days', value: [dayjs().subtract(30, 'day').toDate(), new Date()] },
    { label: 'This month', value: [dayjs().startOf('month').toDate(), dayjs().endOf('month').toDate()] },
    { label: 'Last month', value: [dayjs().subtract(1, 'month').startOf('month').toDate(), dayjs().subtract(1, 'month').endOf('month').toDate()] },
    { label: 'This quarter', value: [dayjs().startOf('quarter').toDate(), dayjs().endOf('quarter').toDate()] },
    { label: 'This year', value: [dayjs().startOf('year').toDate(), dayjs().endOf('year').toDate()] }
];

// Order counts for facilities
const orderCounts = computed(() => ({
    'Orders': totalOrdersCount.value,
    'Transfers': props.transferReceivedCard,
    'Dispenses': 0 // Add dispense count if available
}));

const totalOrders = computed(() =>
    props.orderStats.pending +
    props.orderStats.reviewed +
    props.orderStats.approved +
    props.orderStats.in_process +
    props.orderStats.dispatched +
    props.orderStats.received +
    props.orderStats.rejected +
    props.orderStats.delivered
);

// Order Status Chart Filter
const selectedOrderStatus = ref([]);
const orderStatusOptions = [
    { value: 'pending', label: 'Pending' },
    { value: 'reviewed', label: 'Reviewed' },
    { value: 'approved', label: 'Approved' },
    { value: 'in_process', label: 'In Process' },
    { value: 'dispatched', label: 'Dispatched' },
    { value: 'delivered', label: 'Delivered' },
    { value: 'received', label: 'Received' },
    { value: 'rejected', label: 'Rejected' }
];

// Format large numbers with k, m abbreviations
const formatNumber = (number) => {
    const num = parseFloat(number);
    
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    } else {
        return num.toLocaleString();
    }
};

// Computed properties for filtered data
const filteredTransferReceivedCard = computed(() => props.transferReceivedCard);
const filteredOrdersDelayedCount = computed(() => props.ordersDelayedCount);




// Chart data computed properties
const productCategoryChartData = computed(() => {
    const labels = Object.keys(props.productCategoryCard);
    const data = Object.values(props.productCategoryCard);
    
    // Dynamic color palette that can handle any number of categories
    const colorPalette = [
        '#3B82F6', // blue
        '#10B981', // emerald
        '#F59E0B', // amber
        '#EF4444', // red
        '#8B5CF6', // violet
        '#EC4899', // pink
        '#06B6D4', // cyan
        '#84CC16', // lime
        '#F97316', // orange
        '#6366F1', // indigo
        '#14B8A6', // teal
        '#A855F7', // purple
    ];
    
    // Generate background colors for all categories
    const backgroundColor = labels.map((_, index) => 
        colorPalette[index % colorPalette.length]
    );
    
    return {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: backgroundColor,
            borderWidth: 0,
            hoverBorderWidth: 0,
            borderColor: 'transparent',
            hoverBorderColor: 'transparent',
        }]
    };
});



// Computed properties for dashboard stats
const totalOrdersCount = computed(() => {
    return Object.values(props.orderStats || {}).reduce((sum, count) => {
        const numCount = Number(count) || 0;
        return sum + numCount;
    }, 0);
});

const lowStockCount = computed(() => {
    return props.inventoryStatusCounts?.find(item => item.status === 'low_stock')?.count || 0;
});

const outOfStockCount = computed(() => {
    return props.inventoryStatusCounts?.find(item => item.status === 'out_of_stock')?.count || 0;
});

// Homogenized KPI cards (unified design like warehouse)
const kpiCards = computed(() => [
    {
        key: 'orders',
        label: 'Total Orders',
        value: totalOrdersCount.value || 0,
        route: 'orders.index',
        iconPath: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        accent: 'text-indigo-600',
    },
    {
        key: 'transfers',
        label: 'Transfers',
        value: filteredTransferReceivedCard.value || 0,
        route: 'transfers.index',
        iconPath: 'M8 7h12m0 0l-4-4m4 4l-4 4M4 17h12m0 0l-4 4m4-4l-4-4',
        accent: 'text-purple-600',
    },
    {
        key: 'delayed',
        label: 'Delayed Orders',
        value: filteredOrdersDelayedCount.value || 0,
        route: 'orders.index',
        iconPath: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        accent: 'text-amber-600',
    },
    {
        key: 'low',
        label: 'Low Stock',
        value: lowStockCount.value || 0,
        route: 'inventories.index',
        iconPath: 'M12 9v2m0 4h.01M5 20h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z',
        accent: 'text-orange-600',
    },
    {
        key: 'out',
        label: 'Out of Stock',
        value: outOfStockCount.value || 0,
        route: 'inventories.index',
        iconPath: 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636',
        accent: 'text-red-600',
    },
]);

const orderChartData = computed(() => ({
    labels: Object.keys(orderCounts.value),
    datasets: [{
        label: '',
        data: Object.values(orderCounts.value),
        backgroundColor: ['#3B82F6', '#10B981', '#F59E0B'],
        borderWidth: 0,
        hoverBorderWidth: 0,
        borderColor: 'transparent',
        hoverBorderColor: 'transparent',
    }]
}));

// Ring indicators configuration (match warehouse)
const orderStatusConfig = [
  { key: 'pending', label: 'Pending', stroke: '#eab308', textClass: 'text-yellow-600', icon: '/assets/images/pending.png' },
  { key: 'reviewed', label: 'Reviewed', stroke: '#3b82f6', textClass: 'text-blue-600', icon: '/assets/images/review.png' },
  { key: 'approved', label: 'Approved', stroke: '#10b981', textClass: 'text-emerald-600', icon: '/assets/images/approved.png' },
  { key: 'in_process', label: 'In Process', stroke: '#8b5cf6', textClass: 'text-violet-600', icon: '/assets/images/inprocess.png' },
  { key: 'dispatched', label: 'Dispatched', stroke: '#ec4899', textClass: 'text-pink-600', icon: '/assets/images/dispatch.png' },
  { key: 'delivered', label: 'Delivered', stroke: '#f59e0b', textClass: 'text-amber-600', icon: '/assets/images/delivery.png' },
  { key: 'received', label: 'Received', stroke: '#6366f1', textClass: 'text-indigo-600', icon: '/assets/images/received.png' },
  { key: 'rejected', label: 'Rejected', stroke: '#ef4444', textClass: 'text-red-600', icon: '/assets/images/rejected.png' }
];

const expiredChartData = computed(() => ({
    labels: ['Expired', 'Expiring in 6 Months', 'Expiring in 1 Year'],
    datasets: [{
        data: [
            props.expiredStats.expired || 0,
            props.expiredStats.expiring_within_6_months || 0,
            props.expiredStats.expiring_within_1_year || 0
        ],
        backgroundColor: [
            '#EF4444', // red - expired
            '#F59E0B', // amber - expiring soon
            '#3B82F6', // blue - expiring later
        ],
        borderWidth: 0,
        hoverBorderWidth: 0,
        borderColor: 'transparent',
        hoverBorderColor: 'transparent',
    }]
}));

// Chart options
const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        },
        datalabels: {
            color: '#fff',
            font: {
                weight: 'bold',
                size: 12
            },
            formatter: (value, ctx) => {
                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = ((value / total) * 100).toFixed(1);
                return `${percentage}%`;
            }
        }
    },
    cutout: '40%',
    elements: {
        arc: {
            borderWidth: 0
        }
    },
};

const horizontalBarChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y',
    plugins: {
        legend: {
            display: false
        },
        datalabels: {
            color: '#fff',
            font: {
                weight: 'bold'
            },
            formatter: (value) => {
                return formatNumber(value);
            }
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    return context.dataset.label + ': ' + formatNumber(context.parsed.x);
                }
            }
        }
    },
    scales: {
        x: {
            beginAtZero: true,
            ticks: {
                callback: function(value) {
                    return formatNumber(value);
                }
            },
            grid: { display: false }
        },
        y: {
            grid: { display: false }
        }
    },
    elements: {
        bar: {
            borderWidth: 0,
            borderSkipped: 'left',
            borderRadius: { topRight: 100, bottomRight: 0, topLeft: 0, bottomLeft: 0 },
            maxBarThickness: 22,
            barPercentage: 0.45,
            categoryPercentage: 0.5
        }
    },
    plugins: {
        datalabels: {
            color: '#111827',
            anchor: 'end',
            align: 'end',
            offset: -2,
            font: { weight: 'bold' },
            formatter: (v) => formatNumber(v)
        }
    }
};

const orderChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        datalabels: {
            color: '#ffffff',
            font: {
                weight: 'bold',
                size: 16
            },
            anchor: 'center',
            align: 'center',
            offset: 0,
            formatter: (value) => {
                return value > 0 ? value.toString() : '';
            }
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            borderColor: '#ffffff',
            borderWidth: 1,
            cornerRadius: 8,
            callbacks: {
                label: function(context) {
                    return context.dataset.label + ': ' + formatNumber(context.parsed.y);
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                callback: function(value) {
                    return formatNumber(value);
                },
                color: '#6b7280',
                font: {
                    size: 12,
                    weight: '500'
                }
            },
            grid: { 
                display: true,
                color: '#f3f4f6',
                drawBorder: false
            },
            border: {
                display: false
            }
        },
        x: {
            ticks: {
                color: '#6b7280',
                font: {
                    size: 12,
                    weight: '500'
                }
            },
            grid: { 
                display: false
            },
            border: {
                display: false
            }
        }
    },
    elements: {
        bar: {
            borderWidth: 0,
            hoverBorderWidth: 0,
            borderSkipped: 'bottom',
            borderRadius: { topLeft: 8, topRight: 8, bottomLeft: 0, bottomRight: 0 },
            barThickness: 30,
            maxBarThickness: 40,
            barPercentage: 0.5,
            categoryPercentage: 0.7
        }
    }
};

const orderStatusChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        datalabels: {
            color: '#fff',
            font: {
                weight: 'bold'
            },
            formatter: (value) => {
                return formatNumber(value);
            }
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    return context.dataset.label + ': ' + formatNumber(context.parsed.y);
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                callback: function(value) {
                    return formatNumber(value);
                }
            }
        }
    }
};

// Methods
const clearAllStatuses = () => {
    selectedOrderStatus.value = [];
};

// Watch for date range changes
watch(dateRange, (newRange) => {
    if (newRange && newRange.length === 2) {
        // Handle date range changes if needed
        console.log('Date range changed:', newRange);
    }
});

// ===============================
// TRACEABLE ITEMS FUNCTIONALITY
// ===============================

// Traceable items variables
const facilityDataType = ref('opening_balance');

// Chart data state  
const localFacilityChartData = ref([]);
const facilityChartCount = ref(0);
const facilityCategorizedData = ref([]);

// Computed property to group facility charts into rows of 4
const facilityChartRows = computed(() => {
    const rows = [];
    for (let i = 0; i < localFacilityChartData.value.length; i += 4) {
        rows.push(localFacilityChartData.value.slice(i, i + 4));
    }
    return rows;
});

const isLoadingFacilityChart = ref(false);
const facilityChartError = ref(null);

const months = Array.from({ length: 12 }, (_, i) =>
  dayjs().subtract(i, 'month').format('YYYY-MM')
);
const facilityMonth = ref(months[1]); // Use previous month as default to match backend

// Watch for changes in facility filters
watch(
    [
        () => facilityDataType.value,
        () => facilityMonth.value
    ],
    () => {
        handleFacilityTracertItems();
    }
);

// Get human-readable label for facility data type
function getFacilityTypeLabel(type) {
    const labels = {
        'opening_balance': 'Beginning Balance',
        'stock_received': 'QTY Received',
        'stock_issued': 'Issued Quantity', 
        'closing_balance': 'Closing Balance (Calculated)',
        'positive_adjustments': 'Positive Adjustments',
        'negative_adjustments': 'Negative Adjustments'
    };
    return labels[type] || 'Quantity';
}

async function handleFacilityTracertItems() {
    isLoadingFacilityChart.value = true;
    facilityChartError.value = null;
    
    const query = {};
    if (facilityDataType.value){
        query.type = facilityDataType.value;
    } else {
        query.type = 'opening_balance';
    }
    if (facilityMonth.value){
        query.month = facilityMonth.value;
    }
    // Note: No facility_id parameter needed as it will use auth()->user()->facility_id in backend

    try {
        const response = await axios.post(route('dashboard.facility.tracert-items'), query);
        console.log('Facility API Response:', response.data);
        
        if (response.data.success && response.data.chartData && response.data.chartData.charts) {
            // Handle successful response with multiple charts
            const charts = response.data.chartData.charts;
            localFacilityChartData.value = charts.map(chart => ({
                id: chart.id,
                category: chart.category,
                categoryDisplay: chart.categoryDisplay,
                labels: chart.labels || ['No Data'],
                datasets: [{
                    label: getFacilityTypeLabel(facilityDataType.value),
                    data: chart.data || [0],
                    backgroundColor: chart.backgroundColors || ['rgba(156, 163, 175, 0.8)'],
                    borderColor: chart.borderColors || ['rgba(156, 163, 175, 1)'],
                    borderWidth: 2
                }]
            }));
            facilityChartCount.value = response.data.chartData.totalCharts;
            facilityChartError.value = null;
            
            // Store items data if available
            if (response.data.items) {
                facilityCategorizedData.value = response.data.items;
            }
        } else {
            // Handle API success but no data
            facilityChartError.value = response.data.message || 'No facility data available for the selected period';
            localFacilityChartData.value = [{
                id: 1,
                category: 'No Data',
                categoryDisplay: 'No Data Available',
                labels: ['No Data'],
                datasets: [{
                    label: 'Quantity',
                    data: [0],
                    backgroundColor: ['rgba(156, 163, 175, 0.8)'],
                    borderColor: ['rgba(156, 163, 175, 1)'],
                    borderWidth: 2
                }]
            }];
            facilityChartCount.value = 1;
            facilityCategorizedData.value = [];
        }
    } catch (error) {
        console.error('Error fetching facility tracert items:', error);
        facilityChartError.value = error.response?.data?.message || 'Network error occurred while loading facility data';
        
        // Set empty chart data on error
        localFacilityChartData.value = [{
            id: 1,
            category: 'Error',
            categoryDisplay: 'Error Loading Data',
            labels: ['Error'],
            datasets: [{
                label: 'Quantity',
                data: [0],
                backgroundColor: ['rgba(239, 68, 68, 0.8)'],
                borderColor: ['rgba(239, 68, 68, 1)'],
                borderWidth: 2
            }]
        }];
        facilityChartCount.value = 1;
    } finally {
        isLoadingFacilityChart.value = false;
    }
}

// Chart options for traceable items
const issuedChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        datalabels: {
            color: '#374151',
            font: {
                weight: 'bold',
                size: 10
            },
            formatter: (value) => {
                return formatNumber(value);
            }
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    return context.dataset.label + ': ' + formatNumber(context.parsed.y);
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                precision: 0,
                callback: function(value) {
                    return formatNumber(value);
                }
            },
            grid: { display: false }
        },
        x: {
            grid: { display: false }
        }
    },
    elements: {
        bar: {
            borderWidth: 0,
            hoverBorderWidth: 0,
            borderSkipped: 'bottom',
            borderRadius: { topLeft: 100, topRight: 100, bottomLeft: 0, bottomRight: 0 },
            maxBarThickness: 18,
            barPercentage: 0.45,
            categoryPercentage: 0.55
        }
    },
    plugins: {
        datalabels: {
            color: '#111827',
            anchor: 'end',
            align: 'end',
            offset: -2,
            font: { weight: 'bold' },
            formatter: (v) => formatNumber(v)
        }
    }
};

// Load traceable items on mount
onMounted(() => {
    handleFacilityTracertItems();
});


</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout title="Dashboard" description="Welcome to the dashboard">
        <!-- Quick Stats Cards Row (match warehouse gradient style) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <!-- Blue Card - Delayed Orders -->
            <Link :href="route('orders.index')" class="block">
                <div class="relative overflow-hidden rounded-lg cursor-pointer">
                    <div class="absolute inset-0" style="background: linear-gradient(45deg, #007BFF 0%, #6FB9FF 50%, #D0E7FF 100%);"></div>
                    <div class="relative p-4">
                        <div class="flex flex-col">
                            <h3 class="text-sm font-medium text-white mb-1">Delayed Orders</h3>
                            <div class="text-2xl font-bold text-white">{{ filteredOrdersDelayedCount || 0 }}</div>
                            <div class="text-xs font-light text-white mt-1">{{ new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}</div>
                        </div>
                    </div>
                </div>
            </Link>

            <!-- Teal Card - Transfers -->
            <Link :href="route('transfers.index')" class="block">
                <div class="relative overflow-hidden rounded-lg cursor-pointer">
                    <div class="absolute inset-0" style="background: linear-gradient(45deg, #14B8A6 0%, #5EEAD4 50%, #CCFBF1 100%);"></div>
                    <div class="relative p-4">
                        <div class="flex flex-col">
                            <h3 class="text-sm font-medium text-white mb-1">Transfers</h3>
                            <div class="text-2xl font-bold text-white">{{ filteredTransferReceivedCard || 0 }}</div>
                            <div class="text-xs font-light text-white mt-1">{{ new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}</div>
                        </div>
                    </div>
                </div>
            </Link>

            <!-- Orange Card - Low Stock -->
            <Link :href="route('inventories.index')" class="block">
                <div class="relative overflow-hidden rounded-lg cursor-pointer">
                    <div class="absolute inset-0" style="background: linear-gradient(45deg, #FF8500 0%, #FFB15C 31%, #FFDBB7 100%);"></div>
                    <div class="relative p-4">
                        <div class="flex flex-col">
                            <h3 class="text-sm font-medium text-white mb-1">Low Stock</h3>
                            <div class="text-2xl font-bold text-white">{{ lowStockCount || 0 }}</div>
                            <div class="text-xs font-light text-white mt-1">{{ new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}</div>
                        </div>
                    </div>
                </div>
            </Link>

            <!-- Red Card - Out of Stock -->
            <Link :href="route('inventories.index')" class="block">
                <div class="relative overflow-hidden rounded-lg cursor-pointer">
                    <div class="absolute inset-0" style="background: linear-gradient(45deg, #DC2626 0%, #FF8A8A 50%, #FFE5E5 100%);"></div>
                    <div class="relative p-4">
                        <div class="flex flex-col">
                            <h3 class="text-sm font-medium text-white mb-1">Out of Stock</h3>
                            <div class="text-2xl font-bold text-white">{{ outOfStockCount || 0 }}</div>
                            <div class="text-xs font-light text-white mt-1">{{ new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}</div>
                        </div>
                    </div>
                </div>
            </Link>
        </div>

        <!-- Tracert Items Section -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-6">
            <div class="flex items-center gap-3 mb-6">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Tracert Items</h3>
                    <p class="text-sm text-gray-600 mt-1">Track inventory data by category and time period</p>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-4">
                <div class="flex gap-4">
                    <!-- Note: No facility selector needed - uses auth()->user()->facility_id -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                        <input type="month" v-model="facilityMonth" class="border border-gray-300 rounded-md px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Type</label>
                        <select v-model="facilityDataType" class="border border-gray-300 rounded-md px-3 py-2 min-w-[180px]">
                            <option value="opening_balance">Beginning Balance</option>
                            <option value="stock_received">QTY Received</option>
                            <option value="stock_issued">Monthly Consumption</option>
                            <option value="closing_balance">Closing Balance</option>
                        </select>
                    </div>
                </div>
            </div>
            <!-- Chart Container -->
            <div class="relative" :class="facilityChartCount > 1 ? 'min-h-96' : 'h-80'">
                <!-- Loading State -->
                <div v-if="isLoadingFacilityChart" class="absolute inset-0 flex items-center justify-center bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-2">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                        <span class="text-gray-600">Loading facility chart data...</span>
                    </div>
                </div>
                <!-- Error State -->
                <div v-else-if="facilityChartError" class="absolute inset-0 flex items-center justify-center bg-red-50 rounded-lg">
                    <div class="text-center">
                        <div class="text-red-600 font-medium">{{ facilityChartError }}</div>
                        <button @click="handleFacilityTracertItems" class="mt-2 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Retry
                        </button>
                    </div>
                </div>
                <!-- Charts Grid -->
                <div v-else class="h-full">
                    <!-- Single Chart -->
                    <div v-if="facilityChartCount === 1" class="h-full">
                        <div class="h-full">
                            <Bar :data="localFacilityChartData[0]" :options="issuedChartOptions" />
                        </div>
                    </div>
                    <!-- Multiple Charts Grid - 4 charts per row -->
                    <div v-else class="space-y-6">
                        <div v-for="(chartRow, rowIndex) in facilityChartRows" :key="'facility-row-' + rowIndex" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div v-for="chart in chartRow" :key="'facility-' + chart.id" class="bg-white rounded-lg border border-gray-200 p-3 shadow-sm">
                                <div class="h-64">
                                    <Bar :data="chart" :options="issuedChartOptions" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Select Date Range</span>
                </div>
                <Datepicker
                    v-model="dateRange"
                    range
                    :enable-time-picker="false"
                    :format="{ year: 'numeric', month: 'short', day: 'numeric' }"
                    :placeholder="'Select date range'"
                    :preview-format="'MMM DD, YYYY'"
                    :teleport="true"
                    :auto-apply="true"
                    :min-date="new Date('2020-01-01')"
                    :max-date="new Date('2030-12-31')"
                    :presets="datePresets"
                    class="w-full max-w-md"
                />
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Product Categories Chart -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Product Categories</h3>
                      </div>
                <div class="h-64">
                    <Doughnut :data="productCategoryChartData" :options="doughnutChartOptions" />
                    </div>
                </div>



            <!-- Orders Chart -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Supplies</h3>
                        </div>
                <div class="h-64">
                    <Bar :data="orderChartData" :options="orderChartOptions" />
                            </div>
                        </div>
                    </div>

        <!-- Expiry Status + Quick Start (match warehouse layout) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
            <!-- Expiry Chart - left (8 cols) -->
            <div class="lg:col-span-8 bg-white rounded-xl shadow-lg border border-gray-200 p-3">
                <div class="mb-2">
                    <h3 class="text-xl font-bold text-gray-900">Expiry Status Overview</h3>
                    <p class="text-sm text-gray-600 mt-1">Items by expiry status and timeline</p>
                </div>
                <div class="h-64">
                    <Doughnut :data="expiredChartData" :options="doughnutChartOptions" />
                </div>
            </div>

            <!-- Quick Start grid - right (4 cols) -->
            <div class="lg:col-span-4 grid grid-cols-1 sm:grid-cols-2 grid-rows-2 auto-rows-fr gap-3 h-full">
                <!-- Quick Start placeholder -->
                <div class="relative overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm p-4 min-h-[88px] h-full">
                    <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-amber-400 to-orange-500"></div>
                    <div class="flex items-center justify-between">
                        <div class="text-base font-semibold text-gray-900">Quick Start</div>
                        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-amber-50 text-amber-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.802 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.802-2.034a1 1 0 00-1.175 0l-2.802 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Dispense -->
                <Link :href="route('dispence.index')" class="block group">
                    <div class="relative overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm p-4 transition-all duration-200 hover:shadow-md min-h-[88px] h-full">
                        <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-emerald-400 to-teal-500"></div>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-base font-semibold text-gray-900">Dispense</div>
                            </div>
                            <div class="flex items-center justify-center h-10 w-10 rounded-full bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </Link>

                <!-- Orders -->
                <Link :href="route('orders.index')" class="block group">
                    <div class="relative overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm p-4 transition-all duration-200 hover:shadow-md min-h-[88px] h-full">
                        <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-indigo-400 to-blue-500"></div>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-base font-semibold text-gray-900">Orders</div>
                            </div>
                            <div class="flex items-center justify-center h-10 w-10 rounded-full bg-indigo-50 text-indigo-600 transition-colors group-hover:bg-indigo-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </Link>

                <!-- Transfers -->
                <Link :href="route('transfers.index')" class="block group">
                    <div class="relative overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm p-4 transition-all duration-200 hover:shadow-md min-h-[88px] h-full">
                        <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-violet-400 to-purple-600"></div>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-base font-semibold text-gray-900">Transfers</div>
                            </div>
                            <div class="flex items-center justify-center h-10 w-10 rounded-full bg-violet-50 text-violet-600 transition-colors group-hover:bg-violet-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4M4 17h12m0 0l-4 4m4-4l-4-4"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Order Status Overview (rings like warehouse) -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
            <div class="mb-4">
                <h3 class="text-xl font-bold text-gray-900">Order Status Overview</h3>
                <p class="text-sm text-gray-600 mt-1">Live distribution of orders</p>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
                <div
                    v-for="cfg in orderStatusConfig"
                    :key="cfg.key"
                    class="flex items-center justify-center gap-3 p-3 rounded-lg hover:shadow-sm transition-all"
                >
                    <div class="flex items-center">
                        <div class="w-14 h-14 relative mr-2">
                            <svg class="w-14 h-14 transform -rotate-90">
                                <circle cx="28" cy="28" r="24" fill="none" stroke="#e2e8f0" stroke-width="4" />
                                <circle
                                    cx="28"
                                    cy="28"
                                    r="24"
                                    fill="none"
                                    :stroke="cfg.stroke"
                                    stroke-width="4"
                                    :stroke-dasharray="(totalOrdersCount && totalOrdersCount > 0) ? `${((Number(props.orderStats[cfg.key]) || 0) / totalOrdersCount) * 150.72} 150.72` : '0 150.72'"
                                />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span :class="['text-xs font-bold', cfg.textClass]">
                                    {{ totalOrdersCount > 0 ? Math.round(((Number(props.orderStats[cfg.key]) || 0) / totalOrdersCount) * 100) : 0 }}%
                                </span>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="flex items-center justify-center mb-1">
                                <img :src="cfg.icon" :alt="cfg.label" class="w-6 h-6 mr-2" />
                                <div class="text-base font-semibold text-gray-900">{{ props.orderStats[cfg.key] || 0 }}</div>
                            </div>
                            <div class="text-xs text-gray-600">{{ cfg.label }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </AuthenticatedLayout>
</template>
