<template>
    <AuthenticatedLayout title="Patient Dispensing" description="Manage patient medication dispensing records"
    img="/assets/images/dispence.png">        <div id="printThis">
            <!-- Back button -->
            <div class="mb-6 flex justify-between items-center print:hidden">
                <Link
                    :href="route('dispence.index')"
                    class="flex items-center text-gray-600 hover:text-gray-900"
                >
                    <svg
                        class="w-5 h-5 mr-2"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"
                        />
                    </svg>
                    Back to Dispences
                </Link>
                <button
                    @click="printPrescription"
                    class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors print:hidden"
                >
                    <svg
                        class="w-5 h-5 mr-2"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                        />
                    </svg>
                    Print Prescription
                </button>
            </div>

            <!-- Prescription Card -->
            <div
                id="prescription"
                class="bg-white border-t-8 border-blue-600 mb-[100px] w-[100%] max-w-[100%] mx-auto print:shadow-none print:border-none print:m-4 print:w-full print:max-w-full print:text-black"
            >
                <!-- Facility Header -->
                <div class="bg-gray-50 p-4 border-b">
                    <div class="text-center">
                        <h1 class="text-xl font-bold text-gray-900 mb-1">
                            {{ props.dispence.facility.name }}
                        </h1>
                        <p class="text-gray-600">
                            {{ props.dispence.facility.facility_type }}
                        </p>
                        <div class="text-sm text-gray-600 mt-1">
                            <p>
                                {{ props.dispence.facility.address }} -
                                {{ props.dispence.facility.district }}
                            </p>
                            <p>
                                Tel: {{ props.dispence.facility.phone }} |
                                Email: {{ props.dispence.facility.email }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Header -->
                <div class="p-8">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                                PRESCRIPTION
                            </h2>
                            <p class="text-gray-600">
                                {{ props.dispence.dispence_number }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-gray-600">Date:</p>
                            <p class="font-semibold">
                                {{
                                    moment(props.dispence.dispence_date).format(
                                        "DD/MM/YYYY"
                                    )
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Patient Info -->
                    <div class="mt-6 pt-6 border-t">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-gray-600">Patient Name:</p>
                                <p class="font-semibold text-lg">
                                    {{ props.dispence.patient_name }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600">Phone:</p>
                                <p class="font-semibold">
                                    {{ props.dispence.patient_phone }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Diagnosis -->
                <div class="px-8 pt-8">
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-3">
                            Diagnosis
                        </h2>
                        <p class="text-gray-700 whitespace-pre-line">
                            {{ props.dispence.diagnosis }}
                        </p>
                    </div>
                </div>

                <!-- Medications -->
                <div class="px-8 pb-8">
                    <div class="flex items-center mb-6">
                        <svg
                            class="w-6 h-6 text-blue-600 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                            ></path>
                        </svg>
                        <h2 class="text-xl font-semibold text-gray-800">
                            Medications
                        </h2>
                    </div>

                    <!-- Medications Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed print:text-sm print:border-collapse">
                            <thead>
                                <tr>
                                    <th
                                        class="w-[5%] px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider border border-black"
                                    >
                                        #
                                    </th>
                                    <th
                                        class="w-[30%] px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider border border-black"
                                    >
                                        Medication
                                    </th>
                                    <th
                                        class="w-[10%] px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider border border-black"
                                    >
                                        Dose
                                    </th>
                                    <th
                                        class="w-[15%] px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider border border-black"
                                    >
                                        Frequency
                                    </th>
                                    <th
                                        class="w-[12%] px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider border border-black"
                                    >
                                        Duration
                                    </th>
                                    <th
                                        class="w-[15%] px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider border border-black"
                                    >
                                        Start Date
                                    </th>
                                    <th
                                        class="w-[13%] px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider border border-black"
                                    >
                                        Quantity
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(item, index) in props.dispence
                                        .items"
                                    :key="item.id"
                                >
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm border border-black"
                                    >
                                        {{ index + 1 }}
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap border border-black"
                                    >
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ item.product.name }}
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm border border-black"
                                    >
                                        {{ item.dose }} units
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm border border-black"
                                    >
                                        {{ item.frequency }} times/day
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm border border-black"
                                    >
                                        {{ item.duration }} days
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm border border-black"
                                    >
                                        {{
                                            moment(item.start_date).format(
                                                "DD/MM/YYYY"
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm border border-black"
                                    >
                                        {{ item.quantity }} units
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-8 bg-gray-50 border-t">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-600 text-sm">Dispenced By:</p>
                            <p class="font-medium">
                                {{ props.dispence.dispenced_by?.name }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-gray-600 text-sm">Dispence Date:</p>
                            <p class="font-medium">
                                {{
                                    moment(props.dispence.created_at).format(
                                        "DD/MM/YYYY HH:mm"
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Link } from "@inertiajs/vue3";
import moment from "moment";

const printPrescription = () => {
    window.print();
};

const props = defineProps({
    dispence: Object,
});

// Add minimal print styles
const style = document.createElement("style");
style.textContent = `
    @media print {
        @page {
            size: A4;
            margin-top: 1mm;
            margin-right: 10mm;
            margin-bottom: 10mm;
            margin-left: 10mm;
        }
        html, body {
            width: 100%;
            height: 100%;
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
        }
        body * {
            visibility: hidden !important;
        }
        #printThis,
        #printThis * {
            visibility: visible !important;
        }
        #printThis {
            /* Let it flow naturally within the page margins */
            /* position: absolute removed */
            /* width, height, left, top removed */
            margin: 0 !important;
            padding: 0 !important; /* Content inside #printThis can have its own padding */
            background: white !important;
            font-size: 10pt; /* Base font size for print */
            color: black !important; /* Ensure text is black */
        }

        /* Target the prescription card specifically for print */
        #prescription {
            margin: 0 !important; /* Remove any screen margins for print */
            padding: 0 !important; /* Adjust if internal padding is desired for print */
            border: none !important; /* Remove screen borders for print */
            box-shadow: none !important; /* Remove screen shadows for print */
            width: 100% !important; /* Ensure it uses full available width within #printThis */
            max-width: 100% !important;
        }

        /* Ensure tables and their content scale down if needed */
        #prescription table {
            width: 100% !important;
            table-layout: auto; /* Allow table to adjust column widths */
            font-size: 9pt !important; /* Slightly smaller for table content */
        }
        #prescription th, #prescription td {
            padding: 4px !important; /* Reduce padding in table cells */
            word-break: break-word; /* Allow long words to break */
        }

        /* Resetting styles for layout wrappers */
        div[data-v-app],
        div[data-v-app] > div:first-child,
        div[data-v-app] > div:first-child > div:first-child {
             padding: 0 !important;
             margin: 0 !important;
             background: white !important;
             border: none !important;
             box-shadow: none !important;
        }
    }
`;
document.head.appendChild(style);
</script>
