<template>
    <div>
        <Head title="Monthly Consumption" />
        <AuthenticatedLayout
            title="Monthly Consumption"
            description="Upload and view facility monthly consumption data"
            img="/assets/images/report.png"
        >
            <template #header>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Monthly Consumption
                </h2>
            </template>

            <div class="py-6 space-y-6">
                <!-- Upload card -->
                <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Upload Monthly Consumption Excel</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Upload an Excel file with item rows and month columns to update monthly consumption data
                        for {{ facility?.name }}.
                    </p>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <button
                            type="button"
                            @click="downloadTemplate"
                            class="inline-flex items-center px-4 py-2 bg-white border border-emerald-500 rounded-lg font-medium text-sm text-emerald-700 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                        >
                            <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4h16v4H4zM4 12h4v8H4zM10 12h4v8h-4zM16 12h4v8h-4z">
                                </path>
                            </svg>
                            Download Template
                        </button>

                        <div class="flex flex-1 flex-col sm:flex-row sm:items-center gap-4">
                            <input
                                ref="fileInput"
                                type="file"
                                accept=".xlsx,.xls"
                                @change="handleFileChange"
                                class="hidden"
                            />
                            <button
                                type="button"
                                @click="fileInput && fileInput.click()"
                                :disabled="uploading"
                                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-500 to-emerald-600 border border-transparent rounded-lg font-medium text-sm text-white hover:from-emerald-600 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50"
                            >
                                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 4a1 1 0 011-1h4l1 2h8a1 1 0 01.96 1.27l-2 7A2 2 0 0114 15H8a2 2 0 01-1.94-1.5L5.12 7H4a1 1 0 01-1-1zM7 18a2 2 0 104 0 2 2 0 00-4 0zm6 0a2 2 0 104 0 2 2 0 00-4 0z">
                                    </path>
                                </svg>
                                <span>{{ uploading ? "Uploading..." : "Choose File" }}</span>
                            </button>
                            <button
                                type="button"
                                @click="uploadMonthlyFile"
                                :disabled="!selectedFile || uploading"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                            >
                                <span>Start Upload</span>
                            </button>
                        </div>
                    </div>

                    <p v-if="uploadMessage" class="mt-3 text-sm" :class="uploadSuccess ? 'text-green-700' : 'text-red-600'">
                        {{ uploadMessage }}
                    </p>
                </div>

                <!-- Filters + table -->
                <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                    <div class="flex flex-col lg:flex-row lg:items-end gap-4 mb-4">
                        <div class="flex-1 min-w-[120px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                            <select
                                v-model="selectedYear"
                                class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3"
                            >
                                <option
                                    v-for="y in yearOptions"
                                    :key="y"
                                    :value="y"
                                >
                                    {{ y }}
                                </option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by item</label>
                            <input
                                v-model="itemFilter"
                                type="text"
                                placeholder="Type item name to filter..."
                                class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3"
                            />
                        </div>
                        <div>
                            <button
                                type="button"
                                @click="loadData"
                                :disabled="loading"
                                class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50"
                            >
                                <svg
                                    v-if="loading"
                                    class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                <span>{{ loading ? "Loading..." : "Load Data" }}</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="message" class="mb-4 text-sm text-gray-600">
                        {{ message }}
                    </div>

                    <div v-if="rows.length" class="mb-4 text-sm text-gray-700">
                        <div class="font-semibold">
                            {{ facility?.name }} — Year: {{ selectedYear }}
                        </div>
                        <div class="text-xs text-gray-500">
                            Total items: {{ rows.length }}
                        </div>
                    </div>

                    <div v-if="filteredRows.length" class="overflow-x-auto">
                        <table class="min-w-full border-collapse border border-gray-300">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">
                                        Item
                                    </th>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">
                                        Category
                                    </th>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border border-gray-300">
                                        Dosage Form
                                    </th>
                                    <th class="px-3 py-2 text-right text-xs font-bold text-gray-700 border border-gray-300 bg-yellow-50">
                                        Screened AMC
                                    </th>
                                    <th
                                        v-for="m in months"
                                        :key="m.key"
                                        class="px-3 py-2 text-right text-xs font-bold text-gray-700 border border-gray-300"
                                    >
                                        {{ m.label }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <tr v-for="row in filteredRows" :key="row.product_id" class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-sm text-gray-900 border border-gray-300">
                                        {{ row.item || "—" }}
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-500 border border-gray-300">
                                        {{ row.category || "—" }}
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-500 border border-gray-300">
                                        {{ row.dosage_form || "—" }}
                                    </td>
                                    <td class="px-3 py-2 text-sm text-right text-gray-900 border border-gray-300 bg-yellow-50">
                                        {{ row.amc != null ? formatNum(row.amc) : "" }}
                                    </td>
                                    <td
                                        v-for="m in months"
                                        :key="m.key"
                                        class="px-3 py-2 text-sm text-right text-gray-900 border border-gray-300"
                                    >
                                        {{ row.quantities?.[m.key] != null ? formatNum(row.quantities[m.key]) : "" }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else-if="!loading" class="py-8 text-center text-gray-500 text-sm">
                        No monthly consumption data to display. Select a year and click "Load Data".
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import axios from "axios";

const props = defineProps({
    facility: Object,
    currentYear: Number,
    currentMonth: Number,
    yearOptions: Array,
});

const page = usePage();

const selectedFile = ref(null);
const fileInput = ref(null);
const uploading = ref(false);
const uploadMessage = ref("");
const uploadSuccess = ref(false);

const selectedYear = ref(props.currentYear);
const loading = ref(false);
const months = ref([]);
const rows = ref([]);
const itemFilter = ref("");
const message = ref("");

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (!file) {
        selectedFile.value = null;
        return;
    }

    const ext = file.name.split(".").pop().toLowerCase();
    if (!["xlsx", "xls"].includes(ext)) {
        uploadMessage.value = "Please upload an Excel file (.xlsx or .xls).";
        uploadSuccess.value = false;
        selectedFile.value = null;
        e.target.value = null;
        return;
    }

    selectedFile.value = file;
    uploadMessage.value = "";
};

const uploadMonthlyFile = async () => {
    if (!selectedFile.value) return;

    try {
        uploading.value = true;
        uploadMessage.value = "";

        const formData = new FormData();
        formData.append("file", selectedFile.value);

        const { data } = await axios.post(
            route("monthly-consumption.upload"),
            formData,
            {
                headers: { "Content-Type": "multipart/form-data" },
            }
        );

        uploadSuccess.value = !!data.success;
        uploadMessage.value = data.message || "Upload completed.";
    } catch (e) {
        uploadSuccess.value = false;
        uploadMessage.value =
            e.response?.data?.message || "Upload failed. Please try again.";
    } finally {
        uploading.value = false;
    }
};

const loadData = async () => {
    try {
        loading.value = true;
        message.value = "";
        months.value = [];
        rows.value = [];

        const { data } = await axios.get(
            route("inventories.monthly-consumption.data"),
            {
                params: { year: Number(selectedYear.value) },
            }
        );

        if (!data?.success) {
            message.value =
                data?.message || "Failed to load monthly consumption data.";
            return;
        }

        months.value = data.months || [];
        rows.value = data.rows || [];
        message.value = data.message || "";
    } catch (e) {
        message.value =
            e.response?.data?.message ||
            "Failed to load monthly consumption data.";
    } finally {
        loading.value = false;
    }
};

const downloadTemplate = () => {
    const year = Number(selectedYear.value);
    const url = route("inventories.monthly-consumption.template", { year });
    window.location.href = url;
};

const formatNum = (value) => {
    const num = Number(value || 0);
    if (!Number.isFinite(num)) return "0";
    return new Intl.NumberFormat("en-US", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(num);
};

const filteredRows = computed(() => {
    if (!itemFilter.value.trim()) {
        return rows.value;
    }
    const q = itemFilter.value.toLowerCase();
    return rows.value.filter((r) =>
        (r.item || "").toLowerCase().includes(q)
    );
});
</script>

