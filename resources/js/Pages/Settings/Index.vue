<template>
    <Head title="Settings" />
    <AuthenticatedLayout>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Settings</h2>
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <!-- Tabs -->
                <div class="border-b border-gray-200 mb-4">
                    <nav class="-mb-px flex space-x-8">
                        <button @click="currentTab = 'General'"
                            :class="[currentTab === 'General' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                            General
                        </button>
                        <button @click="currentTab = 'users'"
                            :class="[currentTab === 'users' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                            Users
                        </button>
                        <button @click="currentTab = 'roles'"
                            :class="[currentTab === 'roles' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                            Roles
                        </button>
                        <button @click="currentTab = 'approval'"
                            :class="[currentTab === 'approval' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                            Approval
                        </button>
                    </nav>
                </div>

                <!-- General Tab Content -->
                <div v-show="currentTab === 'General'" class="transition-opacity duration-150" :class="{'opacity-100': currentTab === 'General', 'opacity-0': currentTab !== 'General'}">
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-medium mb-4">General Settings</h3>
                        <p class="text-gray-600">Configure your general application settings here.</p>
                    </div>
                </div>

                <!-- Users Tab Content -->
                <div v-show="currentTab === 'users'" class="transition-opacity duration-150" :class="{'opacity-100': currentTab === 'users', 'opacity-0': currentTab !== 'users'}">
                    <UserIndex :users="props.users" :roles="props.roles" :warehouses="props.warehouses" :filters="props.filters" />
                </div>

                <!-- Roles Tab Content -->
                <div v-show="currentTab === 'roles'" class="transition-opacity duration-150" :class="{'opacity-100': currentTab === 'roles', 'opacity-0': currentTab !== 'roles'}">
                    <RoleIndex :roles="props.roles" :filters="props.filters" />
                </div>

                <!-- Approval Tab Content -->
                <div v-show="currentTab === 'approval'" class="transition-opacity duration-150" :class="{'opacity-100': currentTab === 'approval', 'opacity-0': currentTab !== 'approval'}">
                    <ApprovalIndex :filters="props.filters" :approvals="props.approvals" :roles="props.roles" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UserIndex from '@/Pages/User/Index.vue';
import RoleIndex from '@/Pages/Role/Index.vue';
import ApprovalIndex from '@/Pages/Approval/Index.vue';

const props = defineProps({
    approvals: Object,
    users: Object,
    roles: Array,
    warehouses: Array,
    filters: Object,
    activeTab: {
        type: String,
        default: 'General'
    }
});

const currentTab = ref(props.activeTab);
</script>