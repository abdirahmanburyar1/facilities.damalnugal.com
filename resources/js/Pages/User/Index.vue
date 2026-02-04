<template>
    <div>
        <Head title="User Management" />
        <div class="">
            <div class="bg-white overflow-hidden sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Search and Add User -->
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-4">
                        <div class="w-full md:w-1/3 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search by name, username or email..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                            />
                            <div v-if="processing" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                        <button
                            @click="openModal()"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150 flex items-center justify-center shadow-sm"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 01-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Add New
                        </button>
                    </div>

                    <!-- Users Table -->
                    <div class="overflow-x-auto bg-white rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="group px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" @click="sort('id')">
                                        <div class="flex items-center">
                                            <span>ID</span>
                                            <SortIcon :field="'id'" :current-sort="sort_field" :direction="sort_direction" />
                                        </div>
                                    </th>
                                    <th class="group px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" @click="sort('name')">
                                        <div class="flex items-center">
                                            <span>Name</span>
                                            <SortIcon :field="'name'" :current-sort="sort_field" :direction="sort_direction" />
                                        </div>
                                    </th>
                                    <th class="group px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" @click="sort('username')">
                                        <div class="flex items-center">
                                            <span>Username</span>
                                            <SortIcon :field="'username'" :current-sort="sort_field" :direction="sort_direction" />
                                        </div>
                                    </th>
                                    <th class="group px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" @click="sort('email')">
                                        <div class="flex items-center">
                                            <span>Email</span>
                                            <SortIcon :field="'email'" :current-sort="sort_field" :direction="sort_direction" />
                                        </div>
                                    </th>
                                    <th class="group px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" @click="sort('created_at')">
                                        <div class="flex items-center">
                                            <span>Created</span>
                                            <SortIcon :field="'created_at'" :current-sort="sort_field" :direction="sort_direction" />
                                        </div>
                                    </th>
                                    <th class="group px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition">
                                        <div class="flex items-center">
                                            <span>Health Facility</span>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ user.id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ user.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ user.username }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ user.email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ formatDate(user.created_at) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <span v-if="user.facility" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ user.facility.name }}
                                        </span>
                                        <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Global
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium flex space-x-2">
                                        <button
                                            @click="openRolesModal(user)"
                                            class="text-indigo-600 hover:text-indigo-900 bg-indigo-100 hover:bg-indigo-200 px-3 py-1 rounded-md transition-colors flex items-center"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                            </svg>
                                            Roles
                                        </button>
                                        <button
                                            @click="openModal(user)"
                                            class="text-blue-600 hover:text-blue-900 bg-blue-100 hover:bg-blue-200 px-3 py-1 rounded-md transition-colors flex items-center"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button
                                            @click="confirmDelete(user)"
                                            class="text-red-600 hover:text-red-900 bg-red-100 hover:bg-red-200 px-3 py-1 rounded-md transition-colors flex items-center"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0111 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="users.data.length === 0">
                                    <td colspan="7" class="px-6 py-10 text-center text-gray-500 bg-gray-50">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <span class="text-lg font-medium">No users found</span>
                                            <p class="text-sm mt-1">Try adjusting your search criteria</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6 flex justify-center">
                        <Pagination :links="users.meta.links" />
                    </div>
                </div>
            </div>
        </div>
        <!-- User Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">
                        {{ form.id ? 'Edit User' : 'Create New User' }}
                    </h2>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-500 focus:outline-none" :disabled="isSubmitted || processing">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-4">
                    <div v-if="errors" class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-md">
                        <div v-for="(error, key) in errors" :key="key" class="text-red-700 text-sm flex items-start mb-1 last:mb-0">
                            <svg class="h-4 w-4 mr-1 mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ error }}</span>
                        </div>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-6">
                        <!-- Name and Username in one row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="name" value="Name" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition"
                                    v-model="form.name"
                                    required
                                    :disabled="isSubmitted || processing"
                                    placeholder="Enter full name"
                                />
                            </div>

                            <div>
                                <InputLabel for="username" value="Username" />
                                <TextInput
                                    id="username"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition"
                                    v-model="form.username"
                                    required
                                    :disabled="isSubmitted || processing"
                                    placeholder="Enter username"
                                />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition"
                                v-model="form.email"
                                required
                                :disabled="isSubmitted || processing"
                                placeholder="Enter email address"
                            />
                        </div>

                        <!-- Password with reveal toggle -->
                        <div>
                            <InputLabel for="password" value="Password" :required="!form.id" />
                            <div class="relative">
                                <TextInput
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition"
                                    v-model="form.password"
                                    :required="!form.id"
                                    :disabled="isSubmitted || processing"
                                    placeholder="Enter password"
                                />
                                <button 
                                    type="button" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none"
                                    @click="showPassword = !showPassword"
                                    :disabled="isSubmitted || processing"
                                >
                                    <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                                        <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
                                    </svg>
                                </button>
                            </div>
                            <span v-if="form.id" class="text-sm text-gray-500 mt-1 block">Leave blank to keep current password</span>
                        </div>

                        <!-- Warehouse Selection -->
                        <div>
                            <InputLabel for="warehouse_id" value="Warehouse" />
                            <select
                                id="warehouse_id"
                                v-model="form.warehouse_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition"
                                :disabled="isSubmitted || processing"
                            >
                                <option value="">No Warehouse (Global User)</option>
                                <option v-for="warehouse in props.warehouses" :key="warehouse.id" :value="warehouse.id">
                                    {{ warehouse.name }}
                                </option>
                            </select>
                            <span class="text-sm text-gray-500 mt-1 block">Assign user to a specific warehouse or leave empty for global access</span>
                        </div>

                        <div class="flex items-center justify-end pt-4 border-t border-gray-200">
                            <button
                                type="button"
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition mr-2 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50 disabled:opacity-50"
                                @click="closeModal"
                                :disabled="isSubmitted || processing"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 disabled:opacity-75 flex items-center"
                                :disabled="processing || isSubmitted"
                            >
                                <svg v-if="isSubmitted" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ form.id ? isSubmitted ? 'Updating...' : 'Update User' : isSubmitted ? 'Creating...' : 'Create User' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Modal>

        <!-- Roles Modal -->
        <Modal :show="showRolesModal" @close="closeRolesModal">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Manage User Roles</h2>
                    <button @click="closeRolesModal" class="text-gray-400 hover:text-gray-500 focus:outline-none" :disabled="isSubmitted || processing">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="mt-4">
                    <div v-if="errors" class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-md">
                        <div v-for="(error, key) in errors" :key="key" class="text-red-700 text-sm flex items-start mb-1 last:mb-0">
                            <svg class="h-4 w-4 mr-1 mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ error }}</span>
                        </div>
                    </div>

                    <form @submit.prevent="submitRolesForm" class="space-y-6">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <InputLabel for="roles" value="Assign Roles" />
                                <p class="text-sm text-gray-500 mb-2">Select the roles you want to assign to this user</p>
                                <SelectInput
                                    id="roles"
                                    v-model="rolesForm.roles"
                                    :options="formattedRoles"
                                    multiple
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition"
                                />
                                <div v-if="errors && errors.roles" class="text-red-500 text-sm mt-1">{{ errors.roles }}</div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end pt-4 border-t border-gray-200">
                            <button
                                type="button"
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition mr-2 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50 disabled:opacity-50"
                                @click="closeRolesModal"
                                :disabled="isSubmitted || processing"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 disabled:opacity-75 flex items-center"
                                :disabled="processing || isSubmitted"
                            >
                                <svg v-if="isSubmitted" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ isSubmitted ? 'Saving Roles...' : 'Save Roles' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="closeDeleteModal">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Delete User</h2>
                    <button @click="closeDeleteModal" class="text-gray-400 hover:text-gray-500 focus:outline-none" :disabled="isSubmitted || processing">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="bg-red-50 p-4 rounded-md mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Warning</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button
                        type="button"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50 disabled:opacity-50"
                        @click="closeDeleteModal"
                        :disabled="isSubmitted || processing"
                    >
                        Cancel
                    </button>
                    <button
                        @click="deleteUser"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 disabled:opacity-75 flex items-center"
                        :disabled="processing || isSubmitted"
                    >
                        <svg v-if="isSubmitted" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isSubmitted ? 'Deleting...' : 'Delete User' }}
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import Pagination from '@/Components/Pagination.vue';
import SortIcon from '@/Components/SortIcon.vue';
import { ref, watch, computed } from 'vue';
import { useToast } from 'vue-toastification';
import axios from 'axios';

// Define props
const props = defineProps({
    users: Object,
    roles: Array,
    warehouses: Array,
    filters: Object
});

// Component state
// const page = usePage();
const toast = useToast();
const showModal = ref(false);
const showRolesModal = ref(false);
const showDeleteModal = ref(false);
const processing = ref(false);
const isSubmitted = ref(false);
const errors = ref(null);
const search = ref(props.filters?.search || '');
const showPassword = ref(false);
const sort_direction = ref(props.filters?.sort_direction || 'desc');
const sort_field = ref(props.filters?.sort_field || 'created_at');

// Form data
const form = ref({
    id: null,
    name: '',
    username: '',
    email: '',
    password: '',
    warehouse_id: ''
});

const rolesForm = ref({
    id: null,
    roles: []
});

// Format roles for SelectInput component
const formattedRoles = computed(() => {
    return props.roles.map(role => ({
        id: role.id,
        name: role.name
    }));
});

// Watch search field and call debounced search
watch([
    () => search.value,
    () => sort_field.value,
    () => sort_direction.value
], () => debouncedSearch());

// Debounced search function
function debouncedSearch() {
    const currentUrl = window.location.pathname;
    const params = {};
    
    // Only include non-empty values
    if(search.value) params.search = search.value;
    if (sort_field.value) params.sort_field = sort_field.value;
    if (sort_direction.value) params.sort_direction = sort_direction.value;
    
    if (currentUrl.includes('settings')) {
        router.visit(route('settings.index', { tab: 'users', ...params }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['users', 'filters']
        });
    } else {
        router.visit(route('users.index', params), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['users', 'filters']
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
        router.visit(route('settings.index', { tab: 'users', ...params }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['users', 'filters']
        });
    } else {
        router.visit(route('users.index', params), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['users', 'filters']
        });
    }
}

// Open modal for create/edit
const openModal = (user = null) => {
    console.log(user);
    if (user) {
        form.value.id = user.id;
        form.value.name = user.name;
        form.value.username = user.username;
        form.value.email = user.email;
        form.value.password = '';
        form.value.warehouse_id = user.warehouse_id;
    } else {
        form.value.id = null;
        form.value.name = '';
        form.value.username = '';
        form.value.email = '';
        form.value.password = '';
        form.value.warehouse_id = '';
    }
    showModal.value = true;
};

// Close modal
const closeModal = () => {
    showModal.value = false;
};

// When a form is submitted successfully
const handleFormSuccess = (message) => {
    closeModal();
    toast.success(message);
    
    // Determine current page context
    const isInSettingsPage = window.location.href.includes('settings');
    
    // Refresh data based on context
    if (isInSettingsPage) {
        // When on settings page, refresh the users list
        const params = {};
        if (search.value) params.search = search.value;
        if (sort_field.value) params.sort_field = sort_field.value;
        if (sort_direction.value) params.sort_direction = sort_direction.value;
        
        router.visit(route('settings.index', { tab: 'users', ...params }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['users', 'filters']
        });
    } else {
        // When on users page, refresh data
        const params = {};
        if (search.value) params.search = search.value;
        if (sort_field.value) params.sort_field = sort_field.value;
        if (sort_direction.value) params.sort_direction = sort_direction.value;
        
        router.visit(route('users.index', params), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['users', 'filters']
        });
    }
    
    // Reset processing and isSubmitted flags
    processing.value = false;
    isSubmitted.value = false;
}

// Submit form
const submitForm = async () => {
    // Set processing and isSubmitted flags
    processing.value = true;
    isSubmitted.value = true;
    
    // Determine if we're in settings page
    const isInSettingsPage = window.location.href.includes('settings');
    
    // Use withHeaders to pass the X-From-Settings header
    const headers = isInSettingsPage 
        ? { 'X-From-Settings': 'true' } 
        : {};
        
    await axios.post(route('users.store'), form.value, {
        headers: headers
    })
    .then(response => {
        handleFormSuccess(response.data);
    })
    .catch(error => {
        toast.error(error.response.data);
        console.log(error);
        
        // Reset processing and isSubmitted flags
        processing.value = false;
        isSubmitted.value = false;
    });
};

// Open roles modal
const openRolesModal = (user) => {
    // Reset errors when opening the modal
    errors.value = null;
    
    // Check if user exists and has roles property
    if (user && typeof user === 'object') {
        rolesForm.value = {
            id: user.id,
            roles: user.roles && Array.isArray(user.roles) ? user.roles.map(role => role.id) : []
        };
        showRolesModal.value = true;
    } else {
        toast.error('Invalid user data');
    }
};

// Close roles modal
const closeRolesModal = () => {
    showRolesModal.value = false;
};

// Submit roles form
const submitRolesForm = () => {
    // Set processing and isSubmitted flags
    processing.value = true;
    isSubmitted.value = true;
    
    // Determine if we're in settings page
    const isInSettingsPage = window.location.href.includes('settings');
    
    const roleAssignForm = useForm({
        roles: rolesForm.value.roles,
        _headers: isInSettingsPage 
            ? { 'X-From-Settings': 'true' } 
            : {}
    });
    
    roleAssignForm.post(route('users.roles.assign', rolesForm.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeRolesModal();
            handleFormSuccess('Roles updated successfully');
        },
        onError: (errors) => {
            toast.error("Please correct the errors in the form");
            
            // Reset processing and isSubmitted flags
            processing.value = false;
            isSubmitted.value = false;
        }
    });
};

// Delete user
const confirmDelete = (user) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
    isSubmitted.value = false;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    userToDelete.value = null;
};

const deleteUser = () => {
    if (!userToDelete.value) return;
    
    // Set processing and isSubmitted flags
    processing.value = true;
    isSubmitted.value = true;
    
    // Determine if we're in settings page
    const isInSettingsPage = window.location.href.includes('settings');
    
    const deleteForm = useForm({
        _headers: isInSettingsPage 
            ? { 'X-From-Settings': 'true' } 
            : {}
    });
    
    deleteForm.delete(route('users.destroy', userToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
            handleFormSuccess('User deleted successfully');
        },
        onError: (errors) => {
            toast.error(errors.error || 'An error occurred while deleting the user');
            
            // Reset processing and isSubmitted flags
            processing.value = false;
            isSubmitted.value = false;
        }
    });
};

// Format date
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<style scoped>
/* Add any custom styles here */
</style>