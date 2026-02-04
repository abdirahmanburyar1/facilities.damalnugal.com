<template>
  <div>
    <Head title="Role Management" />

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
      <div class="text-gray-900">
        <div v-if="$page.props.flash && $page.props.flash.success"
          class="mb-4 p-4 bg-green-100 text-green-700 rounded">
          {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.flash && $page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded">
          {{ $page.props.flash.error }}
        </div>

        <!-- Role Creation Form -->
        <div class="mb-8 p-4 bg-gray-50 rounded-lg">
          <h3 class="text-lg font-medium mb-4">Create New Role</h3>
          <form @submit.prevent="createRole">
            <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
              <div>
                <InputLabel for="role-name" value="Role Name" />
                <TextInput id="role-name" type="text" class="mt-1 block w-full" v-model="form.name" required />
                <InputError :message="form.errors.name" class="mt-2" />
              </div>
            </div>

            <div class="mt-4 flex justify-end">
              <PrimaryButton :disabled="form.processing || isSubmitted">
                {{ isSubmitted ? 'Creating...' : 'Create Role' }}
              </PrimaryButton>
            </div>
          </form>
        </div>

        <!-- Roles List -->
        <div>
          <div class="mt-5">
            <div class="pb-5 flex justify-between">
              <div class="text-xl">Roles</div>
              <div class="flex items-center space-x-4">
                <div>
                  <input 
                    type="text" 
                    v-model="search" 
                    placeholder="Search roles..." 
                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                  />
                </div>
                <button @click="showModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded">
                  Add Role
                </button>
              </div>
            </div>
          </div>
          <div v-if="roles.length === 0" class="p-4 bg-gray-50 rounded text-center">
            No roles found. Create your first role using the form above.
          </div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" @click="sort('name')">
                    Name
                    <span v-if="sort_field === 'name'" class="ml-1">
                      {{ sort_direction === 'asc' ? '↑' : '↓' }}
                    </span>
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" @click="sort('created_at')">
                    Created
                    <span v-if="sort_field === 'created_at'" class="ml-1">
                      {{ sort_direction === 'asc' ? '↑' : '↓' }}
                    </span>
                  </th>
                  <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="role in roles" :key="role.id">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ role.name }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ formatDate(role.created_at) }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                    <button @click="editRole(role)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                      Edit
                    </button>
                    <button v-if="role.name !== 'admin'" @click="confirmDeleteRole(role)"
                      class="text-red-600 hover:text-red-900">
                      Delete
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <!-- Edit Role Modal -->
    <Modal :show="editingRole" @close="closeEditModal">
      <div class="p-6 relative overflow-visible">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Edit Role: {{ editForm.name }}</h2>

        <form @submit.prevent="updateRole">
          <div>
            <InputLabel for="edit-role-name" value="Role Name" />
            <TextInput id="edit-role-name" type="text" class="mt-1 block w-full" v-model="editForm.name" required />
            <InputError :message="editForm.errors.name" class="mt-2" />
          </div>

          <div class="mt-6 flex justify-end">
            <SecondaryButton :disabled="editForm.processing || isSubmitted" @click="closeEditModal" class="mr-3">
              Cancel
            </SecondaryButton>
            <PrimaryButton :disabled="editForm.processing || isSubmitted">
              {{ isSubmitted ? 'Updating...' : 'Update Role' }}
            </PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import Swal from 'sweetalert2';
import debounce from 'lodash/debounce';
import { useToast } from 'vue-toastification';

const props = defineProps({
  roles: Array,
  filters: Object
});

// Initialize toast
const toast = useToast();

// Initialize filters with values from props
const search = ref(props.filters?.search || '');
const sort_field = ref(props.filters?.sort_field || 'name');
const sort_direction = ref(props.filters?.sort_direction || 'asc');

// Watch search field and call debounced search
watch(search, debounce(() => {
  debouncedSearch();
}, 300));

// Form for creating a new role
const form = useForm({
  name: ''
});

// Form for editing a role
const editForm = useForm({
  id: null,
  name: ''
});

const editingRole = ref(false);
const currentRole = ref(null);
const isSubmitted = ref(false);

// When a form is submitted successfully
const handleFormSuccess = (message) => {
    toast.success(message);
    
    // Determine current page context
    const isInSettingsPage = window.location.href.includes('settings');
    
    // Refresh data based on context
    if (isInSettingsPage) {
        // When on settings page, refresh the roles list
        const params = {};
        if (search.value) params.search = search.value;
        if (sort_field.value) params.sort_field = sort_field.value;
        if (sort_direction.value) params.sort_direction = sort_direction.value;
        
        router.visit(route('settings.index', { tab: 'roles', ...params }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['roles', 'filters']
        });
    } else {
        // When on roles page, refresh data
        const params = {};
        if (search.value) params.search = search.value;
        if (sort_field.value) params.sort_field = sort_field.value;
        if (sort_direction.value) params.sort_direction = sort_direction.value;
        
        router.visit(route('roles.index', params), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['roles', 'filters']
        });
    }
    
    // Reset processing and isSubmitted flags
    isSubmitted.value = false;
}

// Create a new role
const createRole = () => {
  isSubmitted.value = true;
  
  // Determine if we're in settings page
  const isInSettingsPage = window.location.href.includes('settings');
  
  if (isInSettingsPage) {
    form.transform(data => ({
      ...data,
      _headers: { 'X-From-Settings': 'true' }
    }));
  }
  
  form.post(route('roles.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      handleFormSuccess('Role created successfully');
    },
    onError: () => {
      isSubmitted.value = false;
    }
  });
};

// Open the edit modal for a role
const editRole = (role) => {
  currentRole.value = role;
  editForm.id = role.id;
  editForm.name = role.name;
  editingRole.value = true;
};

// Close the edit modal
const closeEditModal = () => {
  editingRole.value = false;
  editForm.reset();
  currentRole.value = null;
};

// Update a role
const updateRole = () => {
  isSubmitted.value = true;
  
  // Determine if we're in settings page
  const isInSettingsPage = window.location.href.includes('settings');
  
  if (isInSettingsPage) {
    editForm.transform(data => ({
      ...data,
      _headers: { 'X-From-Settings': 'true' }
    }));
  }
  
  editForm.put(route('roles.update', editForm.id), {
    preserveScroll: true,
    onSuccess: () => {
      closeEditModal();
      handleFormSuccess('Role updated successfully');
    },
    onError: () => {
      isSubmitted.value = false;
    }
  });
};

// Confirm deletion of a role
const confirmDeleteRole = (role) => {
  Swal.fire({
    title: 'Are you sure?',
    text: `You are about to delete the role "${role.name}". This action cannot be undone.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!'
  }).then((result) => {
    if (result.isConfirmed) {
      deleteRole();
    }
  });
};

// Delete a role
const deleteRole = () => {
  if (!currentRole.value) return;
  
  isSubmitted.value = true;
  
  // Determine if we're in settings page
  const isInSettingsPage = window.location.href.includes('settings');
  
  const deleteForm = useForm({
    _method: 'DELETE',
    _headers: isInSettingsPage ? { 'X-From-Settings': 'true' } : {}
  });
  
  deleteForm.post(route('roles.destroy', currentRole.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      handleFormSuccess('Role deleted successfully');
    },
    onError: (error) => {
      isSubmitted.value = false;
      Swal.fire({
        title: 'Error',
        text: error.message || 'Failed to delete role',
        icon: 'error'
      });
    }
  });
};

// Search function
function debouncedSearch() {
    const currentUrl = window.location.pathname;
    const params = {};
    
    // Only include non-empty values
    if(search.value) params.search = search.value;
    if (sort_field.value) params.sort_field = sort_field.value;
    if (sort_direction.value) params.sort_direction = sort_direction.value;
    
    if (currentUrl.includes('settings')) {
        router.visit(route('settings.index', { tab: 'roles', ...params }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['roles', 'filters']
        });
    } else {
        router.visit(route('roles.index', params), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['roles', 'filters']
        });
    }
}

// Sort function
function sort(field) {
    sort_field.value = field;
    sort_direction.value = sort_direction.value === 'asc' ? 'desc' : 'asc';
    
    const currentUrl = window.location.pathname;
    const params = {};
    
    // Only include non-empty values
    if (search.value) params.search = search.value;
    if (sort_field.value) params.sort_field = sort_field.value;
    if (sort_direction.value) params.sort_direction = sort_direction.value;
    
    if (currentUrl.includes('settings')) {
        router.visit(route('settings.index', { tab: 'roles', ...params }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['roles', 'filters']
        });
    } else {
        router.visit(route('roles.index', params), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['roles', 'filters']
        });
    }
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}
</script>
