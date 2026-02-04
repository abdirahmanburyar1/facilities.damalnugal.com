<template>
  <Head title="Assign Roles" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Assign Roles to {{ user.name }}</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded">
              {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded">
              {{ $page.props.flash.error }}
            </div>
            
            <!-- User Information -->
            <div class="mb-6">
              <h3 class="text-lg font-medium mb-2">User Information</h3>
              <div class="bg-gray-50 p-4 rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <p class="text-sm text-gray-600">Name</p>
                    <p class="font-medium">{{ user.name }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">Email</p>
                    <p class="font-medium">{{ user.email }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">Username</p>
                    <p class="font-medium">{{ user.username }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">Current Roles</p>
                    <div class="flex flex-wrap gap-1 mt-1">
                      <span 
                        v-for="role in user.roles" 
                        :key="role.id"
                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"
                      >
                        {{ role.name }}
                      </span>
                      <span v-if="user.roles.length === 0" class="text-gray-500 italic">No roles assigned</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Role Assignment Form -->
            <div>
              <h3 class="text-lg font-medium mb-4">Assign Roles</h3>
              <form @submit.prevent="assignRoles">
                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div v-for="role in roles" :key="role.id" class="flex items-start">
                      <div class="flex items-center h-5">
                        <input
                          :id="`role-${role.id}`"
                          type="checkbox"
                          :value="role.id"
                          v-model="form.roles"
                          class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                        />
                      </div>
                      <div class="ml-3 text-sm">
                        <label :for="`role-${role.id}`" class="font-medium text-gray-700">{{ role.name }}</label>
                      </div>
                    </div>
                  </div>
                  
                  <div class="mt-6 flex items-center justify-between">
                    <Link :href="route('users.index')" class="text-sm text-gray-600 hover:text-gray-900">
                      Back to Users
                    </Link>
                    <PrimaryButton :disabled="form.processing || isSubmitted">
                      {{ isSubmitted ? 'Saving...' : 'Save Role Assignments' }}
                    </PrimaryButton>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
  user: Object,
  roles: Array
});

// Initialize form with current user roles
const form = useForm({
  roles: props.user.roles.map(role => role.id)
});

const isSubmitted = ref(false);

// Assign roles to the user
const assignRoles = () => {
  isSubmitted.value = true;
  form.post(route('users.roles.assign', props.user.id), {
    onSuccess: () => {
      isSubmitted.value = false;
      Swal.fire({
        title: 'Roles Assigned!',
        text: 'The roles have been successfully assigned to the user.',
        icon: 'success',
        confirmButtonColor: '#3085d6'
      });
    },
    onError: () => {
      isSubmitted.value = false;
    }
  });
};
</script>
