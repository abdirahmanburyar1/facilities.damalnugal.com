<template>
    <AuthenticatedLayout title="Transfer Details" description="Transfer Details" img="/assets/images/transfer.png">
        <div class="container mx-auto">
        </div>
        <!-- Transfer Header -->
        <div class="mb-6 bg-white rounded-lg shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold text-gray-800">
                    Transfer Details
                </h1>
                <div class="flex items-center space-x-4">
                    <span :class="[
                        statusClasses[props.transfer.status] ||
                        statusClasses.default,
                    ]" class="flex items-center text-xs font-bold px-4 py-2">
                        <!-- Status Icon -->
                        <span class="mr-3">
                            <!-- Pending Icon -->
                            <img v-if="props.transfer.status === 'pending'" src="/assets/images/pending.png"
                                class="w-4 h-4" alt="Pending" />

                            <!-- reviewed Icon -->
                            <img v-else-if="
                                props.transfer.status === 'reviewed'
                            " src="/assets/images/reviewed.png" class="w-4 h-4" alt="Reviewed" />

                            <!-- Approved Icon -->
                            <img v-else-if="
                                props.transfer.status === 'approved'
                            " src="/assets/images/approved.png" class="w-4 h-4" alt="Approved" />

                            <!-- In Process Icon -->
                            <img v-else-if="
                                props.transfer.status === 'in_process'
                            " src="/assets/images/inprocess.png" class="w-4 h-4" alt="In Process" />

                            <!-- Dispatched Icon -->
                            <img v-else-if="
                                props.transfer.status === 'dispatched'
                            " src="/assets/images/dispatch.png" class="w-4 h-4" alt="Dispatched" />

                            <!-- Received Icon -->
                            <img v-else-if="
                                props.transfer.status === 'received'
                            " src="/assets/images/received.png" class="w-4 h-4" alt="Received" />

                            <!-- Rejected Icon -->
                            <svg v-else-if="
                                props.transfer.status === 'rejected'
                            " class="w-4 h-4 text-red-700" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        {{ props.transfer.status.toUpperCase() }}
                    </span>
                </div>
            </div>

            <!-- Transfer ID and Date -->
            <div class="mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">Transfer ID:</span>
                        <span class="ml-2 font-semibold">#{{ props.transfer.transferID }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Transfer Date:</span>
                        <span class="ml-2 font-semibold">{{
                            moment(props.transfer.transfer_date).format(
                                "DD/MM/YYYY"
                            )
                        }}</span>
                    </div>
                </div>
            </div>

            <!-- From and To Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- From Section -->
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold text-blue-800 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        From
                    </h3>
                    <div v-if="props.transfer.from_warehouse">
                        <p class="font-semibold text-gray-800">
                            {{ props.transfer.from_warehouse.name }}
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ props.transfer.from_warehouse.address }}
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ props.transfer.from_warehouse.district }},
                            {{ props.transfer.from_warehouse.region }}
                        </p>
                        <div class="mt-2 text-sm">
                            <p class="text-gray-600">
                                Manager:
                                <span class="font-medium">{{
                                    props.transfer.from_warehouse
                                        .manager_name
                                }}</span>
                            </p>
                            <p class="text-gray-600">
                                Phone:
                                <span class="font-medium">{{
                                    props.transfer.from_warehouse
                                        .manager_phone
                                }}</span>
                            </p>
                        </div>
                        <span class="inline-block mt-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                            Warehouse
                        </span>
                    </div>
                    <div v-else-if="props.transfer.from_facility">
                        <p class="font-semibold text-gray-800">
                            {{ props.transfer.from_facility.name }}
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ props.transfer.from_facility.address }}
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ props.transfer.from_facility.district }},
                            {{ props.transfer.from_facility.region }}
                        </p>
                        <div class="mt-2 text-sm">
                            <p class="text-gray-600">
                                Type:
                                <span class="font-medium">{{
                                    props.transfer.from_facility
                                        .facility_type
                                }}</span>
                            </p>
                            <p class="text-gray-600">
                                Phone:
                                <span class="font-medium">{{
                                    props.transfer.from_facility.phone
                                    }}</span>
                            </p>
                        </div>
                        <span class="inline-block mt-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                            Facility
                        </span>
                    </div>
                </div>

                <!-- To Section -->
                <div class="bg-green-50 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold text-green-800 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        To
                    </h3>
                    <div v-if="props.transfer.to_warehouse">
                        <p class="font-semibold text-gray-800">
                            {{ props.transfer.to_warehouse.name }}
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ props.transfer.to_warehouse.address }}
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ props.transfer.to_warehouse.district }},
                            {{ props.transfer.to_warehouse.region }}
                        </p>
                        <div class="mt-2 text-sm">
                            <p class="text-gray-600">
                                Manager:
                                <span class="font-medium">{{
                                    props.transfer.to_warehouse.manager_name
                                    }}</span>
                            </p>
                            <p class="text-gray-600">
                                Phone:
                                <span class="font-medium">{{
                                    props.transfer.to_warehouse
                                        .manager_phone
                                }}</span>
                            </p>
                        </div>
                        <span class="inline-block mt-2 px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                            Warehouse
                        </span>
                    </div>
                    <div v-else-if="props.transfer.to_facility">
                        <p class="font-semibold text-gray-800">
                            {{ props.transfer.to_facility.name }}
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ props.transfer.to_facility.address }}
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ props.transfer.to_facility.district }},
                            {{ props.transfer.to_facility.region }}
                        </p>
                        <div class="mt-2 text-sm">
                            <p class="text-gray-600">
                                Type:
                                <span class="font-medium">{{
                                    props.transfer.to_facility.facility_type
                                    }}</span>
                            </p>
                            <p class="text-gray-600">
                                Phone:
                                <span class="font-medium">{{
                                    props.transfer.to_facility.phone
                                    }}</span>
                            </p>
                        </div>
                        <span class="inline-block mt-2 px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                            Facility
                        </span>
                    </div>
                </div>
            </div>

            <!-- Status Stage Timeline -->
            <div v-if="props.transfer.status == 'rejected'">
                <div class="flex flex-col items-center">
                    <div
                        class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10 bg-white border-red-500">
                        <img src="/assets/images/rejected.png" class="w-7 h-7" alt="Rejected" />
                    </div>
                    <h1 class="mt-3 text-2xl text-red-600 font-bold ">Rejected</h1>
                </div>
            </div>
            <div v-else class="col-span-2 mb-6">
                <div class="relative">
                    <!-- Timeline Track Background -->
                    <div class="absolute top-7 left-0 right-0 h-2 bg-gray-200 z-0"></div>

                    <!-- Timeline Progress -->
                    <div class="absolute top-7 left-0 h-2 bg-green-500 z-0 transition-all duration-500 ease-in-out"
                        :style="{
                            width: `${(statusOrder.indexOf(props.transfer.status) /
                                (statusOrder.length - 1)) *
                                100
                                }%`,
                        }"></div>

                    <!-- Timeline Steps -->
                    <div class="relative flex justify-between">
                        <!-- Pending -->
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10"
                                :class="[
                                    statusOrder.indexOf(
                                        props.transfer.status
                                    ) >= statusOrder.indexOf('pending')
                                        ? 'bg-white border-orange-500'
                                        : 'bg-white border-gray-200',
                                ]">
                                <img src="/assets/images/pending.png" class="w-7 h-7" alt="Pending" :class="statusOrder.indexOf(
                                    props.transfer.status
                                ) >= statusOrder.indexOf('pending')
                                        ? ''
                                        : 'opacity-40'
                                    " />
                            </div>
                            <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(
                                props.transfer.status
                            ) >= statusOrder.indexOf('pending')
                                    ? 'text-green-600'
                                    : 'text-gray-500'
                                ">Pending</span>
                        </div>

                        <!-- Reviewed -->
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10"
                                :class="[
                                    statusOrder.indexOf(
                                        props.transfer.status
                                    ) >= statusOrder.indexOf('reviewed')
                                        ? 'bg-white border-orange-500'
                                        : 'bg-white border-gray-200',
                                ]">
                                <img src="/assets/images/review.png" class="w-7 h-7" alt="Reviewed" :class="statusOrder.indexOf(
                                    props.transfer.status
                                ) >= statusOrder.indexOf('reviewed')
                                        ? ''
                                        : 'opacity-40'
                                    " />
                            </div>
                            <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(
                                props.transfer.status
                            ) >= statusOrder.indexOf('reviewed')
                                    ? 'text-green-600'
                                    : 'text-gray-500'
                                ">Reviewed</span>
                        </div>

                        <!-- Approved -->
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10"
                                :class="[
                                    statusOrder.indexOf(
                                        props.transfer.status
                                    ) >= statusOrder.indexOf('approved')
                                        ? 'bg-white border-orange-500'
                                        : 'bg-white border-gray-200',
                                ]">
                                <img src="/assets/images/approved.png" class="w-7 h-7" alt="Approved" :class="statusOrder.indexOf(
                                    props.transfer.status
                                ) >= statusOrder.indexOf('approved')
                                        ? ''
                                        : 'opacity-40'
                                    " />
                            </div>
                            <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(
                                props.transfer.status
                            ) >= statusOrder.indexOf('approved')
                                    ? 'text-green-600'
                                    : 'text-gray-500'
                                ">Approved</span>
                        </div>

                        <!-- In Process -->
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10"
                                :class="[
                                    statusOrder.indexOf(
                                        props.transfer.status
                                    ) >= statusOrder.indexOf('in_process')
                                        ? 'bg-white border-orange-500'
                                        : 'bg-white border-gray-200',
                                ]">
                                <img src="/assets/images/inprocess.png" class="w-7 h-7" alt="In Process" :class="statusOrder.indexOf(
                                    props.transfer.status
                                ) >=
                                        statusOrder.indexOf('in_process')
                                        ? ''
                                        : 'opacity-40'
                                    " />
                            </div>
                            <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(
                                props.transfer.status
                            ) >= statusOrder.indexOf('in_process')
                                    ? 'text-green-600'
                                    : 'text-gray-500'
                                ">In Process</span>
                        </div>

                        <!-- Dispatch -->
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10"
                                :class="[
                                    statusOrder.indexOf(
                                        props.transfer.status
                                    ) >= statusOrder.indexOf('dispatched')
                                        ? 'bg-white border-orange-500'
                                        : 'bg-white border-gray-200',
                                ]">
                                <img src="/assets/images/dispatch.png" class="w-7 h-7" alt="Dispatch" :class="statusOrder.indexOf(
                                    props.transfer.status
                                ) >=
                                        statusOrder.indexOf('dispatched')
                                        ? ''
                                        : 'opacity-40'
                                    " />
                            </div>
                            <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(
                                props.transfer.status
                            ) >= statusOrder.indexOf('dispatched')
                                    ? 'text-green-600'
                                    : 'text-gray-500'
                                ">Dispatched</span>
                        </div>

                        <!-- Delivered -->
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10"
                                :class="[
                                    statusOrder.indexOf(
                                        props.transfer.status
                                    ) >= statusOrder.indexOf('delivered')
                                        ? 'bg-white border-orange-500'
                                        : 'bg-white border-gray-200',
                                ]">
                                <img src="/assets/images/delivery.png" class="w-7 h-7" alt="Dispatch" :class="statusOrder.indexOf(
                                    props.transfer.status
                                ) >=
                                        statusOrder.indexOf('delivered')
                                        ? ''
                                        : 'opacity-40'
                                    " />
                            </div>
                            <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(
                                props.transfer.status
                            ) >= statusOrder.indexOf('delivered')
                                    ? 'text-green-600'
                                    : 'text-gray-500'
                                ">Delivered</span>
                        </div>

                        <!-- Received -->
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10"
                                :class="[
                                    statusOrder.indexOf(
                                        props.transfer.status
                                    ) >= statusOrder.indexOf('received')
                                        ? 'bg-white border-green-500'
                                        : 'bg-white border-gray-200',
                                ]">
                                <img src="/assets/images/received.png" class="w-7 h-7" alt="Received" :class="statusOrder.indexOf(
                                    props.transfer.status
                                ) >= statusOrder.indexOf('received')
                                        ? ''
                                        : 'opacity-40'
                                    " />
                            </div>
                            <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(
                                props.transfer.status
                            ) >= statusOrder.indexOf('received')
                                    ? 'text-green-600'
                                    : 'text-gray-500'
                                ">Received</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transfer Items Table -->
            <div class="mb-8">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-100 to-purple-200 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900">Transfer Items</h3>
                        <p class="text-gray-600 text-sm">Detailed breakdown of items being transferred</p>
                    </div>
                </div>

                <div class="bg-white overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left table-sm">
                            <thead>
                                <tr style="background-color: #F4F7FB;">
                                    <th class="min-w-[300px] px-3 py-2 text-xs font-bold rounded-tl-lg" style="color: #4F6FCB;" rowspan="2">
                                        Item Name
                                    </th>
                                    <th class="px-3 py-2 text-xs font-bold" style="color: #4F6FCB;" rowspan="2">
                                        Category
                                    </th>
                                    <th class="px-3 py-2 text-xs font-bold" style="color: #4F6FCB;" rowspan="2">
                                        UoM
                                    </th>
                                    <th class="px-3 py-2 text-xs font-bold text-center" style="color: #4F6FCB;" colspan="4">
                                        Item Details
                                    </th>
                                    <th class="px-3 py-2 text-xs font-bold" style="color: #4F6FCB;" rowspan="2">
                                        Total Quantity on Hand Per Unit
                                    </th>
                                    <th class="px-3 py-2 text-xs font-bold" style="color: #4F6FCB;" rowspan="2">
                                        Transfer Reason
                                    </th>
                                    <th class="px-3 py-2 text-xs font-bold" style="color: #4F6FCB;" rowspan="2">
                                        Quantity to Transfer
                                    </th>
                                    <th class="px-3 py-2 text-xs font-bold" style="color: #4F6FCB;" rowspan="2">
                                        Received Quantity
                                    </th>
                                    <th class="px-3 py-2 text-xs font-bold rounded-tr-lg" style="color: #4F6FCB;" rowspan="2">
                                        Action
                                    </th>
                                </tr>
                                <tr style="background-color: #F4F7FB;">
                                    <th class="px-2 py-1 text-xs font-bold text-center" style="color: #4F6FCB;">
                                        QTY
                                    </th>
                                    <th class="px-2 py-1 text-xs font-bold text-center" style="color: #4F6FCB;">
                                        Batch Number
                                    </th>
                                    <th class="px-2 py-1 text-xs font-bold text-center" style="color: #4F6FCB;">
                                        Expiry Date
                                    </th>
                                    <th class="px-2 py-1 text-xs font-bold text-center" style="color: #4F6FCB;">
                                        Location
                                    </th>
                                </tr>
                            </thead>

                        <tbody>
                            <template v-for="(item, index) in form" :key="item.id">
                    <!-- Show allocations if they exist, otherwise show one row with main item data -->
                            <tr v-for="(allocation, allocIndex) in (item.inventory_allocations?.length > 0 ? item.inventory_allocations : [{}])" :key="`${item.id}-${allocIndex}`" class="hover:bg-gray-50 transition-colors duration-150 border-b" style="border-bottom: 1px solid #B7C6E6;">
                                    <!-- Item Name -->
                                    <td v-if="allocIndex === 0" :rowspan="item.inventory_allocations?.length || 1"
                                        class="px-3 py-2 text-xs font-medium text-gray-800 align-top items-center">
                                        {{ item.product?.name || "N/A" }}
                                    </td>

                                    <!-- Category -->
                                    <td v-if="allocIndex === 0" :rowspan="item.inventory_allocations?.length || 1"
                                        class="px-3 py-2 text-xs text-gray-700 align-top items-center">
                                        {{ item.product?.category?.name || "N/A" }}
                                    </td>

                                    <!-- UoM -->
                                    <td v-if="allocIndex === 0" :rowspan="item.inventory_allocations?.length || 1"
                                        class="px-3 py-2 text-xs text-gray-700 align-top items-center">
                                        {{ item.uom || "N/A" }}
                                    </td>

                                    <!-- QTY -->
                                    <td class="px-2 py-1 text-xs border-b text-center text-gray-900">
                                        {{
                                            (allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0 ? allocation.updated_quantity : allocation.allocated_quantity) ||
                                            0
                                        }}
                                    </td>

                                    <!-- Batch Number -->
                                    <td class="px-2 py-1 text-xs border-b text-center text-gray-900">
                                        <span :class="{
                                            'text-red-600 font-bold':
                                                allocation.batch_number ===
                                                'HK5273',
                                        }">
                                            {{
                                                allocation.batch_number ||
                                                "N/A"
                                            }}
                                        </span>
                                    </td>

                                    <!-- Expiry Date -->
                                    <td class="px-2 py-1 text-xs border-b text-center">
                                        <span :class="{
                                            'text-red-600':
                                                isExpiringItem(
                                                    allocation.expiry_date
                                                ),
                                        }">
                                            {{
                                                allocation.expiry_date
                                                    ? moment(
                                                        allocation.expiry_date
                                                    ).format("DD/MM/YYYY")
                                                    : "N/A"
                                            }}
                                        </span>
                                    </td>

                                    <!-- Location -->
                                    <td class="px-2 py-1 text-xs border-b text-center text-gray-900">
                                        {{
                                            allocation.location ||
                                            "N/A"
                                        }}
                                    </td>

                                    <!-- Total Quantity on Hand -->
                                    <td v-if="allocIndex === 0" :rowspan="item.inventory_allocations?.length || 1"
                                        class="px-3 py-2 text-xs text-gray-800 align-top items-center">
                                        {{ item.quantity_per_unit || 0 }}
                                    </td>

                                    <!-- Transfer Reason -->
                                    <td class="px-2 py-1 text-xs border-b text-center text-gray-900">
                                        {{ allocation.transfer_reason || "N/A" }}
                                    </td>

                                    <!-- Quantity to Transfer -->
                                    <td class="px-2 py-1 text-xs border-b text-center text-gray-900">
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="font-medium">{{ allocation.allocated_quantity }}</span>
                                            <input 
                                                :readonly="!['pending', 'reviewed'].includes(props.transfer.status) || props.transfer.from_facility_id !== $page.props.auth.user?.facility_id"
                                                type="number" 
                                                v-model="allocation.updated_quantity"
                                                :placeholder="(allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0 ? allocation.updated_quantity : allocation.allocated_quantity) || 0"
                                                min="1"
                                                :class="[
                                                    'w-full text-center border border-gray-300 px-1 py-1 text-xs',
                                                    (!['pending', 'reviewed'].includes(props.transfer.status) || props.transfer.from_facility_id !== $page.props.auth.user?.facility_id) ? 'bg-gray-100 cursor-not-allowed' : ''
                                                ]"
                                                @input="handleQuantityInput($event, allocation)"
                                            />
                                            <span class="text-xs text-gray-500" v-if="isUpdatingQuantity[allocation.id]">
                                                Updating...
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Received Quantity -->
                                    <td class="px-2 py-1 text-xs border border-gray-300 text-center text-black"
                                    >
                                        <input 
                                            type="number" 
                                            v-model="allocation.received_quantity" 
                                            :max="getMaxReceivedQuantity(allocation)"
                                            @input="validateReceivedQuantity(allocation, allocIndex)"
                                            min="0"
                                            :readonly="props.transfer.to_facility_id !== $page.props.auth.user?.facility_id || props.transfer.status !== 'delivered'"
                                            :class="[
                                                'w-20 text-center border border-gray-300 rounded px-2 py-1 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500',
                                                (props.transfer.to_facility_id !== $page.props.auth.user?.facility_id || props.transfer.status !== 'delivered') ? 'bg-gray-100 cursor-not-allowed' : ''
                                            ]"
                                        />
                                        <span v-if="isReceived[allocIndex]" class="text-xs text-gray-500">Updating...</span>
                                        <button 
                                            v-if="((allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0 ? allocation.updated_quantity : allocation.allocated_quantity) || 0) !== (allocation.received_quantity || 0) && ['delivered', 'received'].includes(props.transfer.status)"
                                            @click="openBackOrderModal(item, allocation)"
                                            class="text-xs text-orange-600 underline hover:text-orange-800 cursor-pointer mt-1 block">
                                            Back Order
                                        </button>
                                    </td>

                                    <!-- Action -->
                                    <td v-if="allocIndex === 0" :rowspan="item.inventory_allocations?.length || 1" class="px-3 py-2 text-xs text-center align-top items-center">
                                        <button v-if="
                                            props.transfer.status ===
                                            'pending'
                                        " @click="removeItem(index)"
                                            class="text-red-600 hover:text-red-800 transition-colors"
                                            title="Delete item">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

        <!-- dispatch information -->
        <div v-if="props.transfer.dispatch?.length > 0"
            class="mt-8 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">
                    Dispatch Information
                </h2>
            </div>

            <div class="bg-white rounded-lg shadow-lg divide-y divide-gray-200">
                <div v-for="dispatch in props.transfer.dispatch" :key="dispatch.id" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Driver & Company Info -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Driver Information</h3>
                                <div class="mt-2 space-y-2">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span class="text-sm text-gray-900">{{ dispatch.driver?.name }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        <span class="text-sm text-gray-600">{{ dispatch.driver_number }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 6v6m-4-6h8" />
                                        </svg>
                                        <span class="text-sm text-gray-600">ID: {{ dispatch.driver?.driver_id || 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Vehicle Information</h3>
                                <div class="mt-2 space-y-2">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 6v6m-4-6h8" />
                                        </svg>
                                        <span class="text-sm text-gray-900">Plate: {{ dispatch.plate_number }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dispatch Details -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Dispatch Details</h3>
                                <div class="mt-2 space-y-2">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 6v6m-4-6h8" />
                                        </svg>
                                        <span class="text-sm text-gray-900">{{
                                            moment(dispatch.dispatch_date || dispatch.created_at).format('DD/MMM/YYYY') }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <span class="text-sm text-gray-600">{{ dispatch.received_cartons ?? 0 }}/{{ dispatch.no_of_cartoons }} Cartons</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="text-sm text-gray-600">Dispatched on {{
                                            moment(dispatch.created_at).format('MMMM D, YYYY h:mm A') }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-sm text-gray-600">Delivery Images</span>
                                        </div>
                                        <button 
                                            v-if="dispatch.image && dispatch.image !== 'null' && dispatch.image !== ''"
                                            @click="viewDispatchImages(dispatch)"
                                            class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-600 text-xs font-medium rounded-md hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                                            title="View delivery images"
                                        >
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View
                                        </button>
                                        <span v-else class="text-xs text-gray-400">No images</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfer actions -->
        <div class="mt-8 mb-[80px] bg-white rounded-lg shadow-sm">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 text-center">
                Transfer Status Actions
            </h3>
            <div class="flex items-start mb-6">
                <!-- Status Action Buttons -->
                <div class="flex flex-wrap items-center justify-center gap-4 px-1 py-2">

                        <!-- Review Status Display (Read-only) -->
                        <div class="relative">
                            <div class="flex flex-col">
                                <button :class="[
                                    props.transfer.status === 'pending'
                                        ? 'bg-yellow-500'
                                        : statusOrder.indexOf(
                                            props.transfer.status
                                        ) > statusOrder.indexOf('pending')
                                            ? 'bg-green-500'
                                            : 'bg-gray-300',
                                ]"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm text-white min-w-[160px] cursor-default">
                                    <img src="/assets/images/review.png" class="w-5 h-5 mr-2" alt="Review" />
                                    <span class="text-sm font-bold text-white">{{
                                        statusOrder.indexOf(
                                            props.transfer.status
                                        ) > statusOrder.indexOf("pending")
                                            ? "Reviewed"
                                            : props.transfer.status === "pending"
                                                ? "Waiting to be reviewed"
                                                : "Review"
                                    }}</span>
                                </button>
                                <span v-show="props.transfer?.reviewed_at" class="text-sm text-gray-600">
                                    On {{ moment(props.transfer?.reviewed_at).format("DD/MM/YYYY HH:mm") }}
                                </span>
                                <span v-show="props.transfer?.reviewed_by" class="text-sm text-gray-600">
                                    By {{ props.transfer?.reviewed_by?.name }}
                                </span>
                            </div>
                            <div v-if="props.transfer.status === 'pending'"
                                class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse">
                            </div>
                        </div>

                        <!-- Approve Status Display (Read-only) -->
                        <div class="relative" v-if="props.transfer.status !== 'rejected'">
                            <div class="flex flex-col">
                                <button :class="[
                                    props.transfer.status == 'reviewed'
                                        ? 'bg-yellow-500'
                                        : statusOrder.indexOf(
                                            props.transfer.status
                                        ) >
                                            statusOrder.indexOf('reviewed')
                                            ? 'bg-green-500'
                                            : 'bg-gray-300',
                                ]"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm text-white min-w-[160px] cursor-default">
                                    <img src="/assets/images/approved.png" class="w-5 h-5 mr-2" alt="Approve" />
                                    <span class="text-sm font-bold text-white">{{
                                        statusOrder.indexOf(
                                            props.transfer.status
                                        ) >
                                            statusOrder.indexOf("reviewed")
                                            ? "Approved"
                                            : props.transfer.status === "reviewed"
                                                ? "Waiting to be approved"
                                                : "Approve"
                                    }}</span>
                                </button>
                                <span v-show="props.transfer?.approved_at" class="text-sm text-gray-600">
                                    On {{ moment(props.transfer?.approved_at).format("DD/MM/YYYY HH:mm") }}
                                </span>
                                <span v-show="props.transfer?.approved_by" class="text-sm text-gray-600">
                                    By {{ props.transfer?.approved_by?.name }}
                                </span>
                            </div>
                            <div v-if="props.transfer.status === 'reviewed'"
                                class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse">
                            </div>
                        </div>

                        <!-- Process button -->
                        <div class="relative" v-if="props.transfer.status !== 'rejected'">
                            <div class="flex flex-col">
                                <button @click="
                                    changeStatus(
                                        props.transfer.id,
                                        'in_process',
                                        'is_process'
                                    )
                                    " :disabled="isType['is_process'] ||
                                    props.transfer.status !== 'approved' ||
                                    !canDispatch
                                    " :class="[
                                    props.transfer.status === 'approved'
                                        ? 'bg-yellow-500 hover:bg-yellow-600'
                                        : statusOrder.indexOf(
                                            props.transfer.status
                                        ) >
                                            statusOrder.indexOf('approved')
                                            ? 'bg-green-500'
                                            : 'bg-gray-300 cursor-not-allowed',
                                ]"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                                    <svg v-if="
                                        isType['is_process'] &&
                                        props.transfer.status == 'approved'
                                    " class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4">
                                        </circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <template v-else>
                                        <img src="/assets/images/inprocess.png" class="w-5 h-5 mr-2"
                                            alt="Process" />
                                        <span class="text-sm font-bold text-white">{{
                                            statusOrder.indexOf(
                                                props.transfer.status
                                            ) >
                                                statusOrder.indexOf("approved")
                                                ? "Processed"
                                                : isType["is_process"]
                                                    ? "Please Wait..."
                                                    : props.transfer.status ===
                                                        "approved" &&
                                                        !canDispatch
                                                        ? "Waiting to be processed"
                                                        : "Process"
                                        }}</span>
                                    </template>
                                </button>
                                <span v-show="props.transfer?.processed_at" class="text-sm text-gray-600">
                                    On {{ moment(props.transfer?.processed_at).format("DD/MM/YYYY HH:mm") }}
                                </span>
                                <span v-show="props.transfer?.processed_by" class="text-sm text-gray-600">
                                    By {{ props.transfer?.processed_by?.name }}
                                </span>
                            </div>
                            <div v-if="props.transfer.status === 'approved'"
                                class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse">
                            </div>
                        </div>

                        <!-- Dispatch button -->
                        <div class="relative" v-if="props.transfer.status !== 'rejected'">
                            <div class="flex flex-col">
                                <button @click="showDispatchForm = true" :disabled="isType['is_dispatch'] ||
                                    props.transfer.status !==
                                    'in_process' ||
                                    !canDispatch
                                    " :class="[
                                    props.transfer.status === 'in_process'
                                        ? 'bg-yellow-500 hover:bg-yellow-600'
                                        : statusOrder.indexOf(
                                            props.transfer.status
                                        ) >
                                            statusOrder.indexOf('in_process')
                                            ? 'bg-green-500'
                                            : 'bg-gray-300 cursor-not-allowed',
                                ]" class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                                    <svg v-if="
                                        isType['is_dispatch'] &&
                                        props.transfer.status ===
                                        'in_process'
                                    " class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4">
                                        </circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <template v-else>
                                        <img src="/assets/images/dispatch.png" class="w-5 h-5 mr-2"
                                            alt="Dispatch" />
                                        <span class="text-sm font-bold text-white">{{
                                            statusOrder.indexOf(
                                                props.transfer.status
                                            ) >
                                                statusOrder.indexOf(
                                                    "in_process"
                                                )
                                                ? "Dispatched"
                                                : isType["is_dispatch"]
                                                    ? "Please Wait..."
                                                    : props.transfer.status ===
                                                        "in_process" &&
                                                        !canDispatch
                                                        ? "Waiting to be dispatched"
                                                        : "Dispatch"
                                        }}</span>
                                    </template>
                                </button>
                                <span v-show="props.transfer?.dispatched_at" class="text-sm text-gray-600">
                                    On {{ moment(props.transfer?.dispatched_at).format("DD/MM/YYYY HH:mm") }}
                                </span>
                                <span v-show="props.transfer?.dispatched_by" class="text-sm text-gray-600">
                                    By {{ props.transfer?.dispatched_by?.name }}
                                </span>
                            </div>
                            <div v-if="props.transfer.status === 'in_process'"
                                class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse">
                            </div>
                        </div>

                        <!-- Order Delivery Indicators -->
                        <div class="flex flex-col gap-4 sm:flex-row" v-if="props.transfer.status !== 'rejected'">
                            <!-- Delivered Status -->
                            <div class="relative">
                                <div class="flex flex-col">
                                    <button @click="openDeliveryForm()"
                                        :disabled="isType['is_delivering'] || props.transfer?.status != 'dispatched' || !canReceive"
                                        :class="[
                                            props.transfer.status == 'dispatched' && canReceive
                                                ? 'bg-yellow-300'
                                                : statusOrder.indexOf(props.transfer.status) >
                                                    statusOrder.indexOf('dispatched')
                                                    ? 'bg-green-500 cursor-not-allowed'
                                                    : 'bg-gray-300 cursor-not-allowed',
                                        ]"
                                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                                        <img src="/assets/images/delivery.png" class="w-5 h-5 mr-2" alt="delivered" />
                                        <span class="text-sm font-bold text-white">
                                            {{
                                                statusOrder.indexOf(
                                                    props.transfer.status
                                                ) > statusOrder.indexOf("delivered")
                                                    ? "Delivered"
                                                    : isType['is_delivering'] 
                                                        ? 'Please Wait....' 
                                                        : !canReceive 
                                                            ? 'Waiting to be Delivered' 
                                                            : "Mark as Delivered"
                                            }}
                                        </span>
                                    </button>
                                    <span v-show="props.transfer?.delivered_at" class="text-sm text-gray-600">
                                        On {{ moment(props.transfer?.delivered_at).format("DD/MM/YYYY HH:mm") }}
                                    </span>
                                    <span v-show="props.transfer?.delivered_by" class="text-sm text-gray-600">
                                        By {{ props.transfer?.delivered_by?.name }}
                                    </span>
                                </div>

                                <!-- Pulse Indicator if currently at this status -->
                                <div v-if="props.transfer.status === 'dispatched'"
                                    class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse">
                                </div>
                            </div>

                            <!-- Received Status -->
                            <div class="relative">
                                <div class="flex flex-col">
                                    <button @click="
                                        changeStatus(
                                            props.transfer.id,
                                            'received',
                                            'is_receive'
                                        )
                                        " :disabled="isType['is_receive'] ||
                                        props.transfer.status !==
                                        'delivered' ||
                                        !canReceive
                                        " :class="[
                                        props.transfer.status ===
                                            'delivered'
                                            ? 'bg-yellow-500 hover:bg-yellow-600'
                                            : statusOrder.indexOf(
                                                props.transfer.status
                                            ) >
                                                statusOrder.indexOf(
                                                    'delivered'
                                                )
                                                ? 'bg-green-500'
                                                : 'bg-gray-300 cursor-not-allowed',
                                    ]"
                                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                                        <svg v-if="
                                            isType['is_receive'] &&
                                            props.transfer.status ===
                                            'delivered'
                                        " class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <template v-else>
                                            <img src="/assets/images/received.png" class="w-5 h-5 mr-2"
                                                alt="Received" />
                                            <span class="text-sm font-bold text-white">
                                                {{
                                                    statusOrder.indexOf(
                                                        props.transfer.status
                                                    ) >
                                                        statusOrder.indexOf(
                                                            "delivered"
                                                        )
                                                        ? "Received"
                                                        : isType["is_receive"]
                                                            ? "Please Wait..."
                                                            : props.transfer
                                                                .status ===
                                                                "delivered" &&
                                                                !canReceive
                                                                ? "Waiting to be received"
                                                                : "Receive"
                                                }}
                                            </span>
                                        </template>
                                    </button>
                                    <span v-show="props.transfer?.received_at" class="text-sm text-gray-600">
                                        On {{ moment(props.transfer?.received_at).format("DD/MM/YYYY HH:mm") }}
                                    </span>
                                    <span v-show="props.transfer?.received_by" class="text-sm text-gray-600">
                                        By
                                        {{ props.transfer?.received_by?.name }}
                                    </span>
                                </div>

                                <!-- Pulse Indicator if currently at this status -->
                                <div v-if="props.transfer.status === 'delivered'"
                                    class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse">
                                </div>
                            </div>
                        </div>

                        <!-- Status indicator for rejected status -->
                        <div v-if="props.transfer.status === 'rejected'"
                            class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-red-100 text-red-800 min-w-[160px]">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span class="text-sm font-bold">Rejected</span>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Back Order Modal -->
        <Modal :show="showBackOrderModal" @close="attemptCloseModal" maxWidth="2xl">
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div v-if="showIncompleteBackOrderModal" class="rounded-full bg-yellow-100 p-3 mr-3">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ showIncompleteBackOrderModal ? 'Incomplete Back Orders' : 'Back Order Details' }}
                        </h2>
                    </div>
                    <button @click="attemptCloseModal" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Error Message -->
                <div v-if="message" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-800">{{ message }}</p>
                        </div>
                    </div>
                </div>

                <!-- Summary Section -->
                <div class="mb-6 bg-gradient-to-r from-yellow-50 to-orange-50 p-4 rounded-lg border border-yellow-200">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600 font-medium">Product:</span>
                            <p class="text-gray-900 font-semibold">{{ selectedItem?.product?.name }}</p>
                        </div>
                        <div>
                            <span class="text-gray-600 font-medium">Expected:</span>
                            <p class="text-gray-900 font-semibold">{{ getTotalExpectedQuantity() }}</p>
                        </div>
                        <div>
                            <span class="text-gray-600 font-medium">Received:</span>
                            <p class="text-gray-900 font-semibold">{{ getTotalReceivedQuantity() }}</p>
                        </div>
                        <div>
                            <span class="text-gray-600 font-medium">Mismatches:</span>
                            <p class="text-yellow-800 font-semibold">{{ missingQuantity }}</p>
                        </div>
                    </div>
                    
                    <!-- Additional Info for Incomplete Back Orders -->
                    <div v-if="showIncompleteBackOrderModal" class="mt-4 pt-4 border-t border-yellow-200">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600 font-medium">Existing Back Orders:</span>
                                <p class="text-gray-900 font-semibold">{{ totalExistingDifferences }}</p>
                            </div>
                            <div>
                                <span class="text-gray-600 font-medium">Remaining to Allocate:</span>
                                <p class="text-yellow-800 font-semibold">{{ remainingToAllocate }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Batch Information Section -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            Batch Information
                        </h3>
                        <div class="text-sm text-gray-500">
                            Allocate missing quantity ({{ missingQuantity }}) across batches
                        </div>
                    </div>

                    <!-- Batch Cards -->
                    <div class="space-y-4">
                        <div v-for="(allocation, allocIndex) in selectedItem?.inventory_allocations" :key="allocation.id" 
                            class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="bg-indigo-100 rounded-full p-2 mr-3">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900">Batch: {{ allocation.batch_number }}</h4>
                                                                                    <p class="text-xs text-gray-500">
                                                {{ allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0 ? allocation.updated_quantity : allocation.allocated_quantity }} units 
                                                {{ allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0 ? 'updated' : 'allocated' }}
                                            </p>
                                    </div>
                                </div>
                                <button v-if="props.transfer.status !== 'received'"
                                    @click="addBatchBackOrder(allocation)"
                                    :disabled="!canAddMoreToAllocation(allocation) || isSaving"
                                    class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add Issue
                                </button>
                            </div>

                            <!-- Back Order Table for this Batch -->
                            <div v-if="getBatchBackOrders(allocation.id).length > 0" class="mt-4">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Issue Type
                                                </th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Quantity
                                                </th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Notes
                                                </th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="(row, rowIndex) in getBatchBackOrders(allocation.id)" :key="rowIndex" 
                                                class="hover:bg-gray-50 transition-colors duration-150">
                                                <td class="px-4 py-3">
                                                    <select v-model="row.status"
                                                        :disabled="props.transfer.status === 'received'"
                                                        :class="[
                                                            'w-full rounded-lg border-gray-200 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm',
                                                            props.transfer.status === 'received' ? 'bg-gray-100 cursor-not-allowed' : ''
                                                        ]">
                                                        <option v-for="status in ['Missing', 'Damaged', 'Expired', 'Lost']" 
                                                            :key="status" :value="status">
                                                            {{ status }}
                                                        </option>
                                                    </select>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="number" v-model="row.quantity" 
                                                        @input="validateBatchBackOrderQuantity(row, allocation)"
                                                        :disabled="props.transfer.status === 'received'"
                                                        min="0" :max="allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0 ? allocation.updated_quantity : allocation.allocated_quantity"
                                                        :class="[
                                                            'w-full rounded-lg border-gray-200 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm',
                                                            props.transfer.status === 'received' ? 'bg-gray-100 cursor-not-allowed' : ''
                                                        ]" />
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="text" v-model="row.notes" 
                                                        :disabled="props.transfer.status === 'received'"
                                                        placeholder="Optional notes..."
                                                        :class="[
                                                            'w-full rounded-lg border-gray-200 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm',
                                                            props.transfer.status === 'received' ? 'bg-gray-100 cursor-not-allowed' : ''
                                                        ]" />
                                                </td>
                                                <td class="px-4 py-3">
                                                    <button v-if="props.transfer.status !== 'received'"
                                                        @click="removeBatchBackOrder(row, rowIndex)"
                                                        class="text-red-600 hover:text-red-800 transition-colors duration-150">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="mt-6 flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div class="text-sm">
                            <span :class="{ 'text-green-600': isValidForSave, 'text-red-600': !isValidForSave }">
                                {{ totalBatchBackOrderQuantity }}
                            </span>
                            <span class="text-gray-600">/ {{ missingQuantity }} items recorded</span>
                            <div v-if="missingQuantity > 0 && totalBatchBackOrderQuantity === missingQuantity" 
                                class="text-xs text-green-600 mt-1 flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                All missing items accounted for
                            </div>
                            <div v-else-if="missingQuantity > 0 && totalBatchBackOrderQuantity < missingQuantity" 
                                class="text-xs text-yellow-600 mt-1">
                                {{ missingQuantity - totalBatchBackOrderQuantity }} more items need to be allocated
                            </div>
                            <div v-else-if="missingQuantity > 0 && totalBatchBackOrderQuantity > missingQuantity" 
                                class="text-xs text-red-600 mt-1">
                                Over-allocated by {{ totalBatchBackOrderQuantity - missingQuantity }} items
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button :disabled="isSaving" @click="attemptCloseModal"
                            class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                            Exit
                        </button>
                        <button v-if="props.transfer.status !== 'received'"
                            @click="saveBackOrders"
                            :disabled="!isValidForSave || isSaving"
                            class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                            {{ isSaving ? "Saving..." : "Save Differences and Exit" }}
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <Modal :show="showDispatchForm" @close="showDispatchForm = false">
            <div class="p-6 bg-white rounded-md shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    Dispatch Information
                </h2>

                <form @submit.prevent="createDispatch" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Driver</label>
                        <Multiselect v-model="dispatchForm.driver" :options="driverOptions" :searchable="true"
                            :close-on-select="true" :show-labels="false" :allow-empty="true" placeholder="Select Driver"
                            track-by="id" label="name" @select="handleDriverSelect"
                            :class="{ 'border-red-500': dispatchErrors.driver_id }">
                            <template v-slot:option="{ option }">
                                <div>
                                    {{ option.name }}
                                    <span v-if="option.company" class="text-gray-500 text-sm">
                                        ({{ option.company.name }})
                                    </span>
                                </div>
                            </template>
                        </Multiselect>
                        <p v-if="dispatchErrors.driver_id" class="mt-1 text-sm text-red-600">{{
                            dispatchErrors.driver_id[0] }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Dispatch Date</label>
                        <input type="date" v-model="dispatchForm.dispatch_date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                            :class="{ 'border-red-500': dispatchErrors.dispatch_date }">
                        <p v-if="dispatchErrors.dispatch_date" class="mt-1 text-sm text-red-600">{{
                            dispatchErrors.dispatch_date[0] }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Number of Cartons</label>
                        <input type="number" v-model="dispatchForm.no_of_cartoons"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                            :class="{ 'border-red-500': dispatchErrors.no_of_cartoons }">
                        <p v-if="dispatchErrors.no_of_cartoons" class="mt-1 text-sm text-red-600">{{
                            dispatchErrors.no_of_cartoons[0] }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Driver Phone</label>
                        <input type="text" v-model="dispatchForm.driver_number"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                            :class="{ 'border-red-500': dispatchErrors.driver_number }">
                        <p v-if="dispatchErrors.driver_number" class="mt-1 text-sm text-red-600">{{
                            dispatchErrors.driver_number[0] }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Vehicle Plate Number</label>
                        <input type="text" v-model="dispatchForm.plate_number"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                            :class="{ 'border-red-500': dispatchErrors.plate_number }">
                        <p v-if="dispatchErrors.plate_number" class="mt-1 text-sm text-red-600">{{
                            dispatchErrors.plate_number[0] }}</p>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" @click="showDispatchForm = false" :disabled="isSaving"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors duration-150">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSaving"
                            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors duration-150 flex items-center">
                            <span v-if="isSaving" class="mr-2">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                            {{ isSaving ? 'Creating...' : 'Save and Dispatch' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delivery Form Modal -->
        <Modal :show="showDeliveryModal" @close="closeDeliveryForm" maxWidth="4xl">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">
                        Mark Transfer as Delivered
                    </h2>
                    <button @click="closeDeliveryForm" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Dispatch Information Summary -->
                <div v-if="props.transfer.dispatch?.length > 0" class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <h3 class="text-lg font-medium text-blue-900 mb-3">Dispatch Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="dispatch in props.transfer.dispatch" :key="dispatch.id" class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-blue-700">Driver:</span>
                                <span class="text-sm text-blue-800">{{ dispatch.driver?.name || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-blue-700">Phone:</span>
                                <span class="text-sm text-blue-800">{{ dispatch.driver_number || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-blue-700">Plate Number:</span>
                                <span class="text-sm text-blue-800">{{ dispatch.plate_number || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-blue-700">Dispatched Cartons:</span>
                                <span class="text-sm text-blue-800">{{ dispatch.no_of_cartoons || 0 }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-blue-700">Dispatch Date:</span>
                                <span class="text-sm text-blue-800">{{ dispatch.created_at ? new Date(dispatch.created_at).toLocaleDateString() : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delivery Form -->
                <form @submit.prevent="submitDeliveryForm" class="space-y-6">
                    <!-- Received Cartons Section -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Received Cartons</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="dispatch in props.transfer.dispatch" :key="dispatch.id" class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Received Cartons for {{ dispatch.driver?.name || 'Driver' }}
                                </label>
                                <input 
                                    type="number" 
                                    v-model="deliveryForm.received_cartons[dispatch.id]"
                                    :min="0"
                                    :max="dispatch.no_of_cartoons"
                                    @input="validateReceivedCartons(dispatch.id, dispatch.no_of_cartoons)"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    :placeholder="`Max: ${dispatch.no_of_cartoons}`"
                                />
                                <p class="text-xs text-gray-500">
                                    Dispatched: {{ dispatch.no_of_cartoons }} | 
                                    Received: {{ deliveryForm.received_cartons[dispatch.id] || 0 }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Image Upload -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Upload Images</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Received Items Photos {{ hasDiscrepancy ? '(Required)' : '(Optional)' }}
                                </label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600">
                                            <label for="received-images-transfer" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                                <span>Upload images</span>
                                                <input 
                                                    id="received-images-transfer" 
                                                    type="file" 
                                                    multiple 
                                                    accept="image/*"
                                                    @change="handleImageUpload"
                                                    class="sr-only"
                                                />
                                            </label>
                                            <p class="pl-1">or drag and drop</p>
                                        </div>
                                        <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB each</p>
                                    </div>
                                </div>
                                <div v-if="deliveryForm.images.length > 0" class="mt-2">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div v-for="(image, index) in deliveryForm.images" :key="index" class="relative">
                                            <img :src="image.preview" class="h-20 w-full object-cover rounded" />
                                            <button @click="removeImage(index)" class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs">
                                                ×
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Validation Messages -->
                    <div v-if="!isDeliveryFormValid" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Please fix the following issues:</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li v-if="!Object.values(deliveryForm.received_cartons).some(qty => qty > 0)">
                                            At least some cartons must be received
                                        </li>
                                        <li v-if="hasDiscrepancy && deliveryForm.images.length === 0 && !deliveryForm.acknowledgeDiscrepancy">
                                            Either upload images or acknowledge the discrepancy
                                        </li>
                                        <li v-if="!Object.values(deliveryForm.received_cartons).some(qty => qty > 0)">
                                            At least some cartons must be received
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                        <button 
                            type="button"
                            @click="closeDeliveryForm"
                            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit"
                            :disabled="isSubmittingDelivery || !isDeliveryFormValid"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ isSubmittingDelivery ? 'Submitting...' : 'Mark as Delivered' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Dispatch Images Modal -->
        <Modal :show="showDispatchImagesModal" @close="closeDispatchImagesModal" maxWidth="4xl">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">
                        Delivery Images
                    </h2>
                    <button @click="closeDispatchImagesModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div v-if="dispatchImages.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="(image, index) in dispatchImages" :key="index" class="relative group">
                        <img 
                            :src="getImageUrl(image)" 
                            :alt="`Delivery image ${index + 1}`"
                            class="w-full h-64 object-cover rounded-lg shadow-md cursor-pointer transition-transform duration-200 hover:scale-105"
                            @click="openImageLightbox(index)"
                        />
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 rounded-lg flex items-center justify-center">
                            <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No images available</h3>
                    <p class="mt-1 text-sm text-gray-500">No delivery images have been uploaded for this transfer.</p>
                </div>
            </div>
        </Modal>

        <!-- Image Lightbox Modal -->
        <Modal :show="showImageLightbox" @close="closeImageLightbox" maxWidth="6xl">
            <div class="p-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Image {{ currentImageIndex + 1 }} of {{ dispatchImages.length }}
                    </h3>
                    <button @click="closeImageLightbox" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="relative">
                    <img 
                        v-if="dispatchImages[currentImageIndex]"
                        :src="getImageUrl(dispatchImages[currentImageIndex])" 
                        :alt="`Delivery image ${currentImageIndex + 1}`"
                        class="w-full h-auto max-h-[70vh] object-contain mx-auto"
                    />
                    
                    <!-- Navigation buttons -->
                    <button 
                        v-if="currentImageIndex > 0"
                        @click="previousImage"
                        class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-75 transition-all duration-200"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    
                    <button 
                        v-if="currentImageIndex < dispatchImages.length - 1"
                        @click="nextImage"
                        class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-75 transition-all duration-200"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import { useToast } from "vue-toastification";
import axios from "axios";
import moment from "moment";
import { watch } from "vue";
import Swal from "sweetalert2";
import BackOrder from "./BackOrder.vue";
import Multiselect from 'vue-multiselect';
import "vue-multiselect/dist/vue-multiselect.css";
import "@/Components/multiselect.css";

const toast = useToast();
const page = usePage();

const props = defineProps({
    transfer: {
        type: Object,
        required: true,
    },
    drivers: {
        type: Array,
        required: true,
    },
    companyOptions: {
        type: Array,
        required: true,
    },
});

const form = ref([]);
const isLoading = ref(false);

// Quantity update state
const isUpdatingQuantity = ref({});
const updateQuantityTimeouts = ref({});

// Back order modal state
const showBackOrderModal = ref(false);
const selectedItem = ref(null);
const selectedAllocation = ref(null);
const batchBackOrders = ref({});
const showIncompleteBackOrderModal = ref(false);
const isSaving = ref(false);
const message = ref('');

// dispatch info
const showDispatchForm = ref(false);

const dispatchForm = ref({
    driver: null,
    driver_id: "",
    driver_number: "",
    plate_number: "",
    no_of_cartoons: "",
    dispatch_date: "",
    logistic_company_id: "",
    transfer_id: props.transfer?.id,
    status: "Dispatched",
});

const dispatchErrors = ref({});

// delivery modal state
const showDeliveryModal = ref(false);
const isSubmittingDelivery = ref(false);

const deliveryForm = ref({
    received_cartons: {},
    images: [],
    notes: '',
    acknowledgeDiscrepancy: false
});

// dispatch images modal state
const showDispatchImagesModal = ref(false);
const showImageLightbox = ref(false);
const currentImageIndex = ref(0);
const dispatchImages = ref([]);

// Computed properties for delivery form validation
const hasDiscrepancy = computed(() => {
    if (!props.transfer.dispatch?.length) return false;
    
    return props.transfer.dispatch.some(dispatch => {
        const received = deliveryForm.value.received_cartons[dispatch.id] || 0;
        return received < dispatch.no_of_cartoons;
    });
});

const isDeliveryFormValid = computed(() => {
    // At least some cartons must be received
    const hasReceivedCartons = Object.values(deliveryForm.value.received_cartons).some(qty => qty > 0);
    if (!hasReceivedCartons) return false;
    
    // If there's a discrepancy, either upload images or acknowledge
    if (hasDiscrepancy.value && deliveryForm.value.images.length === 0 && !deliveryForm.value.acknowledgeDiscrepancy) {
        return false;
    }
    
    return true;
});

// Computed properties for driver options
const driverOptions = computed(() => {
    return props.drivers.map(driver => ({
        id: driver.id,
        name: driver.name,
        company: driver.company,
        isAddNew: false
    }));
});

onMounted(() => {
    form.value = props.transfer.items || [];
    
    // Set up real-time listeners
    setupRealtimeListeners();
});

onUnmounted(() => {
    // Clean up real-time listeners
    if (window.Echo) {
        // Clean up facility-specific listeners
        if (props.transfer.from_facility_id) {
            window.Echo.leaveChannel(`private-facility-inventory.${props.transfer.from_facility_id}`);
        }
        
        // Clean up transfer status listeners
        window.Echo.leaveChannel(`private-transfer.${props.transfer.id}`);
        
        // Clean up general inventory listeners
        window.Echo.leaveChannel('private-inventory');
        
        console.log('Real-time listeners cleaned up');
    }
});

// Set up real-time listeners for facility inventory updates
const setupRealtimeListeners = () => {
    console.log('Setting up real-time listeners...');
    console.log('Transfer data:', {
        id: props.transfer.id,
        to_facility_id: props.transfer.to_facility_id,
        from_facility_id: props.transfer.from_facility_id,
        to_warehouse_id: props.transfer.to_warehouse_id,
        from_warehouse_id: props.transfer.from_warehouse_id
    });
    console.log('User facility ID:', page.props.auth.user.facility_id);
    
    // Listen for facility inventory updates (only from_facility_id where inventory changes happen)
    if (props.transfer.from_facility_id) {
        console.log('Listening for facility inventory updates for source facility:', props.transfer.from_facility_id);
        window.Echo.private(`private-facility-inventory.${props.transfer.from_facility_id}`)
            .listen('refresh', (data) => {
                console.log('Source facility inventory updated in real-time');
                
                // Show simple notification
                toast.info('Refreshed');
                
                // Refresh the page data
                router.get(route("transfers.show", props.transfer.id), {}, {
                    preserveScroll: true,
                    only: ['transfer'],
                });
            });
    } else {
        console.log('No from_facility_id found, skipping facility inventory listener');
    }
    
    // Listen for transfer status changes
    console.log('Listening for transfer status changes for transfer:', props.transfer.id);
    window.Echo.private(`transfer.${props.transfer.id}`)
        .listen('TransferStatusChanged', (data) => {
            console.log('Transfer status changed in real-time');
            
            // Show simple notification
            toast.info('Refreshed');
            
            // Refresh the page data
            router.get(route("transfers.show", props.transfer.id), {}, {
                preserveScroll: true,
                only: ['transfer'],
            });
        });
    
    // Listen for general inventory updates
    console.log('Listening for general inventory updates');
    window.Echo.private('private-inventory')
        .listen('refresh', (data) => {
            console.log('General inventory updated in real-time');
            
            // Show simple notification
            toast.info('Refreshed');
            
            // Refresh the page data
            router.get(route("transfers.show", props.transfer.id), {}, {
                preserveScroll: true,
                only: ['transfer'],
            });
        });
};

// Status styling
const statusClasses = computed(() => ({
    pending: "bg-yellow-100 text-yellow-800",
    approved: "bg-blue-100 text-blue-800",
    rejected: "bg-red-100 text-red-800",
    in_process: "bg-purple-100 text-purple-800",
    dispatched: "bg-orange-100 text-orange-800",
    delivered: "bg-indigo-100 text-indigo-800",
    received: "bg-green-100 text-green-800",
}));

const isReceived = ref([]);
const receivedQuantityTimeouts = ref({});

async function validateReceivedQuantity(allocation, allocIndex) {
    // Clear existing timeout for this allocation
    if (receivedQuantityTimeouts.value[allocIndex]) {
        clearTimeout(receivedQuantityTimeouts.value[allocIndex]);
    }

    // Set loading state
    isReceived.value[allocIndex] = true;

    // PRIMARY VALIDATION: Check against effective quantity (updated_quantity or allocated_quantity)
    if (allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0) {
        // If updated_quantity is set and greater than 0, received_quantity cannot exceed it
        if (allocation.received_quantity > allocation.updated_quantity) {
            allocation.received_quantity = allocation.updated_quantity;
            toast.warning(`Received quantity cannot exceed updated quantity. Reset to ${allocation.updated_quantity}`);
            return; // Exit early - don't proceed with back order validation
        }
    } else {
        // If no updated_quantity or it's 0, validate against allocated_quantity
        if (allocation.received_quantity > allocation.allocated_quantity) {
            allocation.received_quantity = allocation.allocated_quantity;
            toast.warning(`Received quantity cannot exceed allocated quantity. Reset to ${allocation.allocated_quantity}`);
            return; // Exit early - don't proceed with back order validation
        }
    }
    
    // SECONDARY VALIDATION: Check if there are existing back orders
    // Only proceed if primary validation passed
    if (allocation.differences && allocation.differences.length > 0) {
        const totalBackOrderQuantity = allocation.differences.reduce((sum, diff) => sum + (diff.quantity || 0), 0);
        
        if (allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0) {
            // If updated_quantity is set and greater than 0, use it minus back orders
            const maxReceivedQuantity = allocation.updated_quantity - totalBackOrderQuantity;
            if (allocation.received_quantity > maxReceivedQuantity) {
                allocation.received_quantity = maxReceivedQuantity;
                toast.warning(`Received quantity cannot exceed updated quantity minus back orders. Reset to ${maxReceivedQuantity}`);
            }
        } else {
            // If no updated_quantity or it's 0, use allocated_quantity minus back orders
            const maxReceivedQuantity = allocation.allocated_quantity - totalBackOrderQuantity;
            if (allocation.received_quantity > maxReceivedQuantity) {
                allocation.received_quantity = maxReceivedQuantity;
                toast.warning(`Received quantity cannot exceed allocated quantity minus back orders. Reset to ${maxReceivedQuantity}`);
            }
        }
    }

    // Set new timeout with 500ms delay for debouncing
    receivedQuantityTimeouts.value[allocIndex] = setTimeout(async () => {
        await axios.post(route("transfers.received-quantity"), {
            allocation_id: allocation.id,
            received_quantity: allocation.received_quantity,
            // Backend should handle:
            // 1. Update received_quantity for the allocation
            // 2. If allocated_quantity == received_quantity, DELETE ALL PackingListDifference records for this allocation_id
            // 3. Recalculate total back order quantity for the entire transfer
            // 4. This ensures no orphaned back order records exist when quantities are fully received
        })
        .then((response) => {
            console.log(response.data);
            isReceived.value[allocIndex] = false;
            router.get(route("transfers.show", props.transfer.id), {}, {
                preserveScroll: true,
                only: ['transfer'],
            });
        })
        .catch((error) => {
            isReceived.value[allocIndex] = false;
            toast.error(error.response?.data || "Failed to update received quantity");
            console.log(error);
        });
    }, 500);
}

function addBatchBackOrderRow(allocationId) {

    const allocation = selectedItem.value.inventory_allocations.find(allocation => allocation.id == allocationId);

    batchBackOrders.value.push({
        id: null,
        inventory_allocation_id: allocationId,
        batch_number: allocation.batch_number,
        barcode: allocation.barcode,
        quantity: 0,
        status: "Missing",
        notes: "",
        transfer_item_id: selectedItem.value.id,
    });

}

// Methods
const isExpiringItem = (expiryDate) => {
    if (!expiryDate) return false;
    const expiry = moment(expiryDate);
    const now = moment();
    const daysUntilExpiry = expiry.diff(now, "days");
    return daysUntilExpiry <= 30; // Consider items expiring within 30 days as expiring
};

// Get maximum received quantity for an allocation (considering back orders)
const getMaxReceivedQuantity = (allocation) => {
    if (!allocation) return 0;
    
    // Use updated_quantity if it's set (not null/undefined and greater than 0), otherwise use allocated_quantity
    const effectiveQuantity = allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0 ? allocation.updated_quantity : allocation.allocated_quantity;
    
    // If there are differences (back orders), subtract them from the effective quantity
    if (allocation.differences && allocation.differences.length > 0) {
        const totalDifferences = allocation.differences.reduce((sum, diff) => sum + (diff.quantity || 0), 0);
        return Math.max(0, effectiveQuantity - totalDifferences);
    }
    
    return effectiveQuantity || 0;
};

// Get total expected quantity for the selected item
const getTotalExpectedQuantity = () => {
    if (!selectedItem.value) return 0;
    
    let totalExpectedQuantity = 0;
    
    if (selectedItem.value.inventory_allocations) {
        selectedItem.value.inventory_allocations.forEach(allocation => {
            // Use updated_quantity if it's set (not null/undefined and greater than 0), otherwise use allocated_quantity
            const effectiveQuantity = allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0 ? allocation.updated_quantity : allocation.allocated_quantity;
            totalExpectedQuantity += effectiveQuantity || 0;
        });
    }
    
    return totalExpectedQuantity;
};

// Get total received quantity for the selected item
const getTotalReceivedQuantity = () => {
    if (!selectedItem.value) return 0;
    
    let totalReceivedQuantity = 0;
    
    if (selectedItem.value.inventory_allocations) {
        selectedItem.value.inventory_allocations.forEach(allocation => {
            totalReceivedQuantity += allocation.received_quantity || 0;
        });
    }
    
    return totalReceivedQuantity;
};

const removeItem = (index) => {
    if (
        confirm("Are you sure you want to remove this item from the transfer?")
    ) {
        form.value.splice(index, 1);
        // TODO: Implement API call to remove item from transfer
        console.log("Removed item at index:", index);
    }
};

// update quantity
const isUpading = ref([]);

// Allocation-based quantity update functions
const handleQuantityInput = (event, allocation) => {
    // Clear existing timeout for this allocation
    if (updateQuantityTimeouts.value[allocation.id]) {
        clearTimeout(updateQuantityTimeouts.value[allocation.id]);
    }

    // Set new timeout with 500ms delay
    updateQuantityTimeouts.value[allocation.id] = setTimeout(() => {
        updateAllocationQuantity(event, allocation);
    }, 500);
};



const updateAllocationQuantity = async (event, allocation) => {
    const newQuantity = parseInt(event.target.value);
    
    if (!newQuantity || newQuantity <= 0) {
        toast.error("Please enter a valid quantity");
        // Reset input to effective quantity
        const effectiveQuantity = allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0 ? allocation.updated_quantity : allocation.allocated_quantity;
        event.target.value = effectiveQuantity;
        return;
    }

    // Check if transfer is eligible for updates
    if (!['pending', 'reviewed'].includes(props.transfer.status)) {
        toast.error("Cannot update quantity for transfers that are not in pending status");
        // Reset input to effective quantity
        const effectiveQuantity = allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0 ? allocation.updated_quantity : allocation.allocated_quantity;
        event.target.value = effectiveQuantity;
        return;
    }

    isUpdatingQuantity.value[allocation.id] = true;

    await axios.post(route("transfers.update-quantity"), {
        allocation_id: allocation.id,
        quantity: newQuantity,
    })
    .then(() => {
        isUpdatingQuantity.value[allocation.id] = false;
        
        // Reload the page to show updated values with preserveScroll
        router.get(route("transfers.show", props.transfer.id), {}, {preserveScroll: true});
    })
    .catch((error) => {
        isUpdatingQuantity.value[allocation.id] = false;
        console.error(error);
        toast.error(error.response?.data || "Failed to update quantity");
        // Reset input to effective quantity on error
        const effectiveQuantity = allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0 ? allocation.updated_quantity : allocation.allocated_quantity;
        event.target.value = effectiveQuantity;
    });
};

// Functions for back order modal
const openBackOrderModal = (item, allocation = null) => {
    console.log('Opening back order modal for item:', item);
    console.log('Selected allocation:', allocation);

    showBackOrderModal.value = true;
    selectedItem.value = item;
    selectedAllocation.value = allocation;
    
    // Initialize batchBackOrders with existing differences for THIS SPECIFIC ALLOCATION
    batchBackOrders.value = {};
    
    // If allocation has existing differences, load them
    if (allocation.differences && allocation.differences.length > 0) {
        console.log('Found existing differences for allocation:', allocation.differences);
        
        if (!batchBackOrders.value[allocation.id]) {
            batchBackOrders.value[allocation.id] = [];
        }
        
        allocation.differences.forEach((difference) => {
            batchBackOrders.value[allocation.id].push({
                id: difference.id,
                transfer_item_id: item.id,
                inventory_allocation_id: allocation.id,
                quantity: difference.quantity,
                status: difference.status,
                notes: difference.notes || '',
                isExisting: true
            });
        });
        
        console.log('Initialized batchBackOrders with existing differences:', batchBackOrders.value);
    } else {
        console.log('No existing differences found, starting with empty form');
        if (!batchBackOrders.value[allocation.id]) {
            batchBackOrders.value[allocation.id] = [];
        }
    }
};

// Get batch back orders for a specific allocation
const getBatchBackOrders = (allocationId) => {
    if (!batchBackOrders.value[allocationId]) {
        batchBackOrders.value[allocationId] = [];
    }
    return batchBackOrders.value[allocationId];
};

// Add batch back order
const addBatchBackOrder = (allocation) => {
    if (!batchBackOrders.value[allocation.id]) {
        batchBackOrders.value[allocation.id] = [];
    }
    
    batchBackOrders.value[allocation.id].push({
        transfer_item_id: selectedItem.value.id,
        inventory_allocation_id: allocation.id,
        quantity: 0,
        status: 'Missing',
        notes: '',
        isExisting: false
    });
};

// Remove batch back order
const removeBatchBackOrder = (row, index) => {
    if (batchBackOrders.value[row.inventory_allocation_id]) {
        batchBackOrders.value[row.inventory_allocation_id].splice(index, 1);
    }
};

// Validate batch back order quantity
const validateBatchBackOrderQuantity = (row, allocation) => {
    const currentQuantity = Number(row.quantity) || 0;
    const allocationDifferences = getBatchBackOrders(allocation.id);
    
    // Calculate total quantity for this allocation
    const totalForAllocation = allocationDifferences.reduce((sum, diffRow) => {
        if (diffRow !== row) {
            return sum + (Number(diffRow.quantity) || 0);
        }
        return sum;
    }, 0) + currentQuantity;
    
    // Check if total exceeds effective quantity (updated_quantity or allocated_quantity)
    const effectiveQuantity = allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0 ? allocation.updated_quantity : allocation.allocated_quantity;
    if (totalForAllocation > effectiveQuantity) {
        row.quantity = effectiveQuantity - totalForAllocation + currentQuantity;
    }
    
    // Ensure quantity is not negative
    if (row.quantity < 0) {
        row.quantity = 0;
    }
};

// Check if we can add more back orders to an allocation
const canAddMoreToAllocation = (allocation) => {
    if (!selectedItem.value) return false;
    
    if (missingQuantity.value <= 0) return false;
    
    // Get current back orders for this allocation
    const currentBackOrders = getBatchBackOrders(allocation.id);
    const totalBackOrdered = currentBackOrders.reduce(
        (sum, row) => sum + (Number(row.quantity) || 0), 0
    );
    
    // Calculate remaining quantity that can be allocated
    const remainingOverall = missingQuantity.value - totalBatchBackOrderQuantity.value;
    
    const effectiveQuantity = allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0 ? allocation.updated_quantity : allocation.allocated_quantity;
    
    return totalBackOrdered < effectiveQuantity && remainingOverall > 0;
};

// Computed properties for back order modal
const missingQuantity = computed(() => {
    if (!selectedItem.value) return 0;
    
    // Calculate total expected quantity based on effective quantities of all allocations
    let totalExpectedQuantity = 0;
    let totalReceivedQuantity = 0;
    
    if (selectedItem.value.inventory_allocations) {
        selectedItem.value.inventory_allocations.forEach(allocation => {
            // Use updated_quantity if it's set (not null/undefined and greater than 0), otherwise use allocated_quantity
            const effectiveQuantity = allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0 ? allocation.updated_quantity : allocation.allocated_quantity;
            totalExpectedQuantity += effectiveQuantity || 0;
            totalReceivedQuantity += allocation.received_quantity || 0;
        });
    }
    
    return totalExpectedQuantity - totalReceivedQuantity;
});

const totalBatchBackOrderQuantity = computed(() => {
    let total = 0;
    Object.values(batchBackOrders.value).forEach((rows) => {
        rows.forEach((row) => {
            total += Number(row.quantity) || 0;
        });
    });
    return total;
});

const remainingToAllocate = computed(() => {
    return missingQuantity.value - totalBatchBackOrderQuantity.value;
});

const totalExistingDifferences = computed(() => {
    let total = 0;
    Object.values(batchBackOrders.value).forEach((rows) => {
        rows.forEach((row) => {
            if (row.isExisting) {
                total += Number(row.quantity) || 0;
            }
        });
    });
    return total;
});

const isValidForSave = computed(() => {
    // Check if we have any back orders
    const hasBackOrders = Object.values(batchBackOrders.value).some(
        (rows) => rows.length > 0
    );
    
    // Check if all back orders have valid data
    const allValid = Object.values(batchBackOrders.value).every((rows) => {
        return rows.every((row) => row.quantity > 0 && row.status);
    });
    
    // Check if total matches missing quantity
    const totalMatches = totalBatchBackOrderQuantity.value === missingQuantity.value;
    
    return hasBackOrders && allValid && totalMatches;
});

// Save back orders
const saveBackOrders = async () => {
    console.log(batchBackOrders.value);
    message.value = "";  
    
    if (totalBatchBackOrderQuantity.value !== missingQuantity.value) {
        message.value = `The total difference quantity (${totalBatchBackOrderQuantity.value}) must exactly match the missing quantity (${missingQuantity.value}).`;
        return;
    }
    
    // Validate that all rows have required fields
    const allValid = Object.values(batchBackOrders.value).every((rows) => {
        return rows.every((row) => row.quantity > 0 && row.status);
    });
    
    if (!allValid) {
        message.value = "Please ensure all rows have valid quantity and status values.";
        return;
    }
    
    isSaving.value = true;
    
    // Flatten the batchBackOrders object into an array
    const differenceData = [];
    Object.entries(batchBackOrders.value).forEach(([allocationId, rows]) => {
        rows.forEach((row) => {
            differenceData.push({
                id: row.id,
                transfer_item_id: row.transfer_item_id,
                inventory_allocation_id: row.inventory_allocation_id,
                quantity: row.quantity,
                status: row.status,
                notes: row.notes
            });
        });
    });
    
    await axios.post(route("transfers.save-back-orders"), {
        transfer_id: props.transfer.id,
        packing_list_differences: differenceData,
    })
        .then((response) => {
            isSaving.value = false;
            console.log(response.data);
            toast.success("Back orders saved successfully");
            message.value = "";
            
            // Close the modal after successful save
            showBackOrderModal.value = false;
            showIncompleteBackOrderModal.value = false;
            
            // Refresh the page to show updated data (backend will handle recalculation)
            router.get(route("transfers.show", props.transfer.id), {}, {
                preserveScroll: true,
                only: ['transfer'],
            });
        })
        .catch((error) => {
            isSaving.value = false;
            console.log(error);
            message.value = error.response?.data || "Failed to save back orders";
        });
};

const attemptCloseModal = () => {
    // If transfer status is 'received', allow free exit regardless of validation issues
    if (props.transfer.status === 'received') {
        showBackOrderModal.value = false;
        showIncompleteBackOrderModal.value = false;
        return;
    }
    
    // For other statuses, check validation as before
    if (totalBatchBackOrderQuantity.value > 0 && totalBatchBackOrderQuantity.value !== missingQuantity.value) {
        showIncompleteBackOrderModal.value = true;
    } else {
        showBackOrderModal.value = false;
        showIncompleteBackOrderModal.value = false;
    }
};

const forceCloseModal = () => {
    showBackOrderModal.value = false;
    showIncompleteBackOrderModal.value = false;
};

const isType = ref([]);
// Define status order for progression
const statusOrder = ref([
    "pending",
    "reviewed",
    "approved",
    "in_process",
    "dispatched",
    "delivered",
    "received",
]);

const canApprove = computed(() => {
    return true;
});

const canDispatch = computed(() => {
    const auth = page.props.auth;
    return auth.user.facility_id === props.transfer.from_facility_id;
});

const canReceive = computed(() => {
    const auth = page.props.auth;
    return auth.user.facility_id == props.transfer.to_facility_id;
});

// Function to change transfer status
const changeStatus = (transferId, newStatus, type) => {
    // Get action name for better messaging
    const actionMap = {
        reviewed: "review",
        approved: "approve",
        in_process: "process",
        dispatched: "dispatch",
        delivered: "mark as delivered",
        received: "receive",
    };

    const actionName = actionMap[newStatus] || "change status of";

    Swal.fire({
        title: "Are you sure?",
        text: `Are you sure to make this Transfer ${newStatus.charAt(0).toUpperCase() +
            newStatus.slice(1).replace("_", " ")
            }?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: `Yes, ${actionName}!`,
    }).then(async (result) => {
        if (result.isConfirmed) {
            // Set loading state
            isType.value[type] = true;

            try {
                const response = await axios.post(
                    // route("transfers.change-status"),
                    route("transfers.changeItemStatus"),
                    {
                        transfer_id: transferId,
                        status: newStatus,
                    }
                );

                // Reset loading state
                isType.value[type] = false;

                Swal.fire({
                    title: "Success!",
                    text: `Transfer has been ${actionMap[newStatus] || "updated"
                        }d successfully.`,
                    icon: "success",
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                }).then(() => {
                    // Reload the page to show the updated status
                    router.get(route("transfers.show", props.transfer.id));
                });
            } catch (error) {
                // Reset loading state
                isType.value[type] = false;

                // Extract error message from response
                let errorMessage = "Failed to update transfer status";

                if (error.response) {
                    if (error.response.status === 403) {
                        errorMessage =
                            error.response.data ||
                            "You don't have permission to perform this action";
                    } else if (error.response.status === 400) {
                        errorMessage =
                            error.response.data || "Invalid operation";
                    } else if (error.response.data) {
                        errorMessage = error.response.data;
                    }
                } else if (error.message) {
                    errorMessage = error.message;
                }

                Swal.fire({
                    title: "Error!",
                    text: errorMessage,
                    icon: "error",
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 5000, // Show error longer than success
                });
            }
        }
    });
};

// Enhanced dispatch methods
const handleDriverSelect = (selectedDriver) => {
    if (selectedDriver) {
        dispatchForm.value.driver_id = selectedDriver.id;
        dispatchForm.value.driver_number = selectedDriver.phone || '';
        dispatchForm.value.logistic_company_id = selectedDriver.company?.id || '';
    }
};

async function createDispatch() {
    isSaving.value = true;
    dispatchErrors.value = {};

    try {
        const response = await axios.post(route("transfers.dispatch-info"), dispatchForm.value);
        
        isSaving.value = false;
        showDispatchForm.value = false;
        
        Swal.fire({
            title: "Success!",
            text: response.data,
            icon: "success",
            confirmButtonText: "OK",
        }).then(() => {
            router.get(route("transfers.show", props.transfer?.id));
        });
    } catch (error) {
        isSaving.value = false;
        
        if (error.response?.data?.errors) {
            dispatchErrors.value = error.response.data.errors;
        } else {
            toast.error(error.response?.data || "Failed to create dispatch");
        }
    }
}

// Delivery modal methods
const openDeliveryForm = () => {
    showDeliveryModal.value = true;
    // Initialize received cartons form
    deliveryForm.value.received_cartons = {};
    if (props.transfer.dispatch?.length > 0) {
        props.transfer.dispatch.forEach(dispatch => {
            deliveryForm.value.received_cartons[dispatch.id] = 0;
        });
    }
    deliveryForm.value.images = [];
    deliveryForm.value.notes = '';
    deliveryForm.value.acknowledgeDiscrepancy = false;
};

const closeDeliveryForm = () => {
    showDeliveryModal.value = false;
    deliveryForm.value.received_cartons = {};
    deliveryForm.value.images = [];
    deliveryForm.value.notes = '';
    deliveryForm.value.acknowledgeDiscrepancy = false;
};

const handleImageUpload = (event) => {
    const files = Array.from(event.target.files);
    files.forEach(file => {
        if (file.size > 10 * 1024 * 1024) { // 10MB limit
            toast.error(`File ${file.name} is too large. Maximum size is 10MB.`);
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            deliveryForm.value.images.push({
                file: file,
                preview: e.target.result
            });
        };
        reader.readAsDataURL(file);
    });
};

const removeImage = (index) => {
    deliveryForm.value.images.splice(index, 1);
};

const validateReceivedCartons = (dispatchId, maxCartons) => {
    const currentValue = deliveryForm.value.received_cartons[dispatchId] || 0;
    if (currentValue > maxCartons) {
        deliveryForm.value.received_cartons[dispatchId] = maxCartons;
        toast.warning(`Received cartons cannot exceed ${maxCartons}. Reset to ${maxCartons}.`);
    }
};

const submitDeliveryForm = async () => {
    if (!isDeliveryFormValid.value) {
        toast.error('Please fix the validation errors before submitting.');
        return;
    }

    isSubmittingDelivery.value = true;

    try {
        // Create FormData for file upload
        const formData = new FormData();
        formData.append('transfer_id', props.transfer.id);
        formData.append('notes', deliveryForm.value.notes);
        formData.append('acknowledge_discrepancy', deliveryForm.value.acknowledgeDiscrepancy);
        
        // Add received cartons data
        Object.entries(deliveryForm.value.received_cartons).forEach(([dispatchId, quantity]) => {
            formData.append(`received_cartons[${dispatchId}]`, quantity);
        });
        
        // Add images
        deliveryForm.value.images.forEach((image, index) => {
            formData.append(`images[${index}]`, image.file);
        });

        const response = await axios.post(route("transfers.mark-delivered"), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        isSubmittingDelivery.value = false;
        closeDeliveryForm();

        Swal.fire({
            title: "Success!",
            text: "Transfer has been marked as delivered successfully.",
            icon: "success",
            confirmButtonText: "OK",
        }).then(() => {
            router.get(route("transfers.show", props.transfer?.id));
        });

    } catch (error) {
        isSubmittingDelivery.value = false;
        console.error('Delivery submission error:', error);
        
        let errorMessage = "Failed to mark transfer as delivered";
        if (error.response?.data) {
            errorMessage = error.response.data;
        }
        
        toast.error(errorMessage);
    }
};

// Dispatch images modal methods
const openDispatchImagesModal = () => {
    showDispatchImagesModal.value = true;
    loadDispatchImages();
};

const closeDispatchImagesModal = () => {
    showDispatchImagesModal.value = false;
    dispatchImages.value = [];
};

const viewDispatchImages = (dispatch) => {
    dispatchImages.value = [];
    
    if (dispatch.image) {
        try {
            const images = JSON.parse(dispatch.image);
            if (Array.isArray(images)) {
                dispatchImages.value.push(...images);
            } else if (typeof images === 'string') {
                // If it's a single image path as string
                dispatchImages.value.push(images);
            }
        } catch (e) {
            // If parsing fails, treat it as a single image path
            if (typeof dispatch.image === 'string') {
                dispatchImages.value.push(dispatch.image);
            }
            console.error('Error parsing dispatch images:', e);
        }
    }
    
    showDispatchImagesModal.value = true;
};

const loadDispatchImages = () => {
    dispatchImages.value = [];
    
    if (props.transfer.dispatch?.length) {
        props.transfer.dispatch.forEach(dispatch => {
            if (dispatch.image) {
                try {
                    const images = JSON.parse(dispatch.image);
                    if (Array.isArray(images)) {
                        dispatchImages.value.push(...images);
                    } else if (typeof images === 'string') {
                        // If it's a single image path as string
                        dispatchImages.value.push(images);
                    }
                } catch (e) {
                    // If parsing fails, treat it as a single image path
                    if (typeof dispatch.image === 'string') {
                        dispatchImages.value.push(dispatch.image);
                    }
                    console.error('Error parsing dispatch images:', e);
                }
            }
        });
    }
};

const getImageUrl = (imagePath) => {
    // Convert storage path to public URL
    if (!imagePath) return '';
    return '/' + imagePath;
};

const openImageLightbox = (index) => {
    if (dispatchImages.value[index]) {
        currentImageIndex.value = index;
        showImageLightbox.value = true;
    }
};

const closeImageLightbox = () => {
    showImageLightbox.value = false;
    currentImageIndex.value = 0;
};

const previousImage = () => {
    if (currentImageIndex.value > 0) {
        currentImageIndex.value--;
    }
};

const nextImage = () => {
    if (currentImageIndex.value < dispatchImages.value.length - 1) {
        currentImageIndex.value++;
    }
};

const isSavingQty = ref([]);
async function receivedQty(item, index) {
    isSavingQty.value[index] = true;
    // console.log(item, index);
    if (item.quantity_to_release < item.received_quantity) {
        item.received_quantity = item.quantity_to_release;
    }

    await axios
        .post(route("transfers.receivedQuantity"), {
            transfer_item_id: item.id,
            received_quantity: item.received_quantity,
        })
        .then((response) => {
            isSavingQty.value[index] = false;
        })
        .catch((error) => {
            console.log(error.response.data);
            isSavingQty.value[index] = false;
        });
    // 'orders.receivedQuantity
}

// Auto-validate received quantities when component mounts or data changes
const autoValidateReceivedQuantities = () => {
    if (props.transfer && props.transfer.items) {
        props.transfer.items.forEach(item => {
            if (item.inventory_allocations) {
                item.inventory_allocations.forEach(allocation => {
                    const currentReceivedQuantity = allocation.received_quantity || 0;
                    
                    // PRIMARY VALIDATION: Check against effective quantity (updated_quantity or allocated_quantity)
                    if (allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0) {
                        // If updated_quantity is set and greater than 0, received_quantity cannot exceed it
                        if (currentReceivedQuantity > allocation.updated_quantity) {
                            allocation.received_quantity = allocation.updated_quantity;
                            toast.warning(`Received quantity cannot exceed updated quantity. Reset to ${allocation.updated_quantity}`);
                            return; // Exit early - don't proceed with back order validation
                        }
                    } else {
                        // If no updated_quantity or it's 0, check against allocated_quantity
                        if (currentReceivedQuantity > allocation.allocated_quantity) {
                            allocation.received_quantity = allocation.allocated_quantity;
                            toast.warning(`Received quantity cannot exceed allocated quantity. Reset to ${allocation.allocated_quantity}`);
                            return; // Exit early - don't proceed with back order validation
                        }
                    }
                    
                    // SECONDARY VALIDATION: Check if there are existing back orders
                    // Only proceed if primary validation passed
                    if (allocation.differences && allocation.differences.length > 0) {
                        const totalBackOrderQuantity = allocation.differences.reduce((sum, diff) => sum + (diff.quantity || 0), 0);
                        
                        if (allocation.updated_quantity !== null && allocation.updated_quantity !== undefined && allocation.updated_quantity > 0) {
                            // If updated_quantity is set and greater than 0, use it minus back orders
                            const maxReceivedQuantity = allocation.updated_quantity - totalBackOrderQuantity;
                            if (allocation.received_quantity > maxReceivedQuantity) {
                                allocation.received_quantity = maxReceivedQuantity;
                                toast.warning(`Received quantity cannot exceed updated quantity minus back orders. Reset to ${maxReceivedQuantity}`);
                            }
                        } else {
                            // If no updated_quantity or it's 0, use allocated_quantity minus back orders
                            const maxReceivedQuantity = allocation.allocated_quantity - totalBackOrderQuantity;
                            if (allocation.received_quantity > maxReceivedQuantity) {
                                allocation.received_quantity = maxReceivedQuantity;
                                toast.warning(`Received quantity cannot exceed allocated quantity minus back orders. Reset to ${maxReceivedQuantity}`);
                            }
                        }
                    }
                });
            }
        });
    }
};

// Watch for changes in transfer data and auto-validate
watch(() => props.transfer, () => {
    autoValidateReceivedQuantities();
}, { immediate: true, deep: true });

// Also validate when component mounts
onMounted(() => {
    autoValidateReceivedQuantities();
});
</script>
