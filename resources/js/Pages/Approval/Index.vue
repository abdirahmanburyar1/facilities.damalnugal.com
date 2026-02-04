<template>
    <!-- <AuthenticatedLayout title="Approvals"> -->
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Approvals
        </h2>

        <div>
            <div class="bg-white overflow-hidden sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <input type="text" v-model="search" placeholder="Search" class="w-1/2 rounded border-2 border-gray-300" />
                        <div class="flex justify-between mb-6">
                            <button @click="openCreateModal()"
                                    class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700">
                                Add New Approval
                            </button>
                        </div>
                    </div>

                    <div v-if="page.props.flash && page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
                        {{ page.props.flash.success }}
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Role
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Activity Type
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Approval Level
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Description
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="props.approvals.data.length > 0" v-for="approval in props.approvals.data" :key="approval.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ approval.role ? approval.role.name : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                                :class="{
                                                'bg-blue-100 text-blue-800': approval.activity_type === 'transfer',
                                                'bg-green-100 text-green-800': approval.activity_type === 'order',
                                                'bg-purple-100 text-purple-800': approval.activity_type === 'all'
                                                }">
                                            {{ approval.activity_type ? (approval.activity_type.charAt(0).toUpperCase() + approval.activity_type.slice(1)) : 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ approval.approval_level }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                                :class="{
                                                'bg-green-100 text-green-800': approval.is_active,
                                                'bg-red-100 text-red-800': !approval.is_active
                                                }">
                                            {{ approval.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ approval.description || 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openEditModal(approval)"
                                                class="text-indigo-600 hover:text-indigo-900 mr-4">
                                            Edit
                                        </button>
                                        <button @click="confirmDelete(approval)"
                                                class="text-red-600 hover:text-red-900">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="props.approvals.data.length === 0">
                                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        No approvals found. Click "Add New Approval" to create one.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Create/Edit Approval Modal -->
        <Modal :show="approvalModal" @close="closeApprovalModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editMode ? 'Edit Approval' : 'Create New Approval' }}
                </h2>
                
                <form @submit.prevent="submitApproval">
                    <div class="mb-4">
                        <InputLabel for="role_id" value="Role" />
                        <SelectInput
                            id="role_id"
                            v-model="form.role_id"
                            :options="roles"
                            class="mt-1 block w-full"
                            placeholder="Select a role"
                            required
                        />
                        <InputError :message="form.errors.role_id" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <InputLabel for="activity_type" value="Activity Type" />
                        <SelectInput
                            id="activity_type"
                            v-model="form.activity_type"
                            :options="activityTypeOptions"
                            class="mt-1 block w-full"
                            placeholder="Select activity type"
                            required
                        />
                        <InputError :message="form.errors.activity_type" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <InputLabel for="approval_level" value="Approval Level" />
                        <TextInput
                            id="approval_level"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.approval_level"
                            required
                        />
                        <InputError :message="form.errors.approval_level" class="mt-2" />
                        <div class="text-sm text-gray-500 mt-1">
                            Higher level means higher priority in the approval chain
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex items-center">
                            <Checkbox id="is_active" :checked="form.is_active" @update:checked="form.is_active = $event" />
                            <InputLabel for="is_active" value="Active" class="ml-2" />
                        </div>
                        <InputError :message="form.errors.is_active" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <InputLabel for="description" value="Description (Optional)" />
                        <TextArea
                            id="description"
                            class="mt-1 block w-full"
                            v-model="form.description"
                            rows="3"
                        />
                        <InputError :message="form.errors.description" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <SecondaryButton @click="closeApprovalModal" class="mr-3" :disabled="form.processing">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            {{ editMode ? form.processing ? 'Updating...' : 'Update Approval' : form.processing ? 'Creating...' : 'Create Approval' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="deleteModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Delete Approval
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Are you sure you want to delete this approval? This action cannot be undone.
                </p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal" class="mr-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Cancel
                    </SecondaryButton>
                    <DangerButton @click="deleteApproval" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        {{ form.processing ? 'Deleting...' : 'Delete Approval' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    <!-- </AuthenticatedLayout> -->
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import SelectInput from '@/Components/SelectInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const props = defineProps({
    approvals: Object,
    roles: {
        type: Array,
        required: true
    },
    filters: Object
});

const page = usePage();

const deleteModal = ref(false);
const approvalModal = ref(false);
const editMode = ref(false);
const selectedApproval = ref(null);
const roles = ref(props.roles || []);

// Initialize toast
const toast = useToast();

// Form for approval creation/editing
const form = useForm({
    id: '',
    role_id: '',
    activity_type: '',
    approval_level: '1',
    is_active: true,
    description: '',
});
const search = ref(props.filters.search || '');
watch([
    () => search.value
], () => reloadApproval());


const activityTypeOptions = [
    { id: 'transfer', name: 'Transfer' },
    { id: 'order', name: 'Order' },
    { id: 'all', name: 'All Activities' }
];

const resetForm = () => {
    form.id = '';
    form.role_id = '';
    form.activity_type = '';
    form.approval_level = '1';
    form.is_active = true;
    form.description = '';
    form.clearErrors();
};

const openCreateModal = () => {
    editMode.value = false;
    resetForm();
    approvalModal.value = true;
};

const openEditModal = (approval) => {
    editMode.value = true;
    resetForm();
    form.id = approval.id;
    form.role_id = approval.role_id;
    form.activity_type = approval.activity_type;
    form.approval_level = String(approval.approval_level);
    form.is_active = approval.is_active === undefined ? true : approval.is_active;
    form.description = approval.description || '';
    approvalModal.value = true;
};

const closeApprovalModal = () => {
    approvalModal.value = false;
    setTimeout(() => {
        form.reset();
        form.clearErrors();
    }, 300);
};

function reloadApproval(){
    const query = {}
    if(search.value) query.search = search.value
    router.get(route('settings.index'), { tab: 'approvals', ...query }, { preserveState: true, preserveScroll: true , only: ['approvals', 'roles']})
}

const submitApproval = async () => {
    form.processing = true;
    
    await axios.post(route('approvals.store'), form)
        .then(response => {
            approvalModal.value = false;
            resetForm();
            toast.success(response.data);
            form.processing = false;
            
            // Refresh the page to show updated data
            reloadApproval();
        })
        .catch(error => {
            form.processing = false;
           toast.error(error.response.data || 'An unexpected error occurred. Please try again later.');
        });
};

const confirmDelete = (approval) => {
    selectedApproval.value = approval;
    deleteModal.value = true;
};

const closeModal = () => {
    deleteModal.value = false;
    selectedApproval.value = null;
};

const deleteApproval = () => {
    form.processing = true;
    
    axios.delete(route('approvals.destroy', selectedApproval.value.id))
        .then(response => {
            form.processing = false;
            closeModal();
            toast.success(response.data);
            // Refresh the page to show updated data
            reloadApproval();
        })
        .catch(error => {
            form.processing = false;
            toast.error(error.response.data || 'An unexpected error occurred. Please try again later.');
            console.error('Delete error:', error);
        });
};
</script>