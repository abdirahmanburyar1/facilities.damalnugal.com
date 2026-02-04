<template>
  <AuthenticatedLayout title="Facility Reorder Levels" description="Manage facility-specific reorder levels" img="/assets/images/settings.png">
    <Head title="Facility Reorder Levels" />

    <div class="bg-white shadow-sm rounded-lg p-4 mb-4">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="flex items-center gap-2">
          <input v-model="search" @keyup.enter="reload" type="text" class="border border-gray-300 rounded px-2 py-1 text-sm" placeholder="Search item..." />
          <button @click="reload" class="px-3 py-1 text-sm bg-blue-600 text-white rounded">Search</button>
        </div>
        <div class="flex items-center gap-2">
          <Link :href="route('inventories.index')" class="px-3 py-1 text-sm bg-gray-200 text-gray-800 rounded">Back to Inventory</Link>
          <button @click="openImportModal" class="px-3 py-1 text-sm bg-amber-600 text-white rounded">Upload Bulk</button>
          <button @click="showAddModal=true" class="px-3 py-1 text-sm bg-green-600 text-white rounded">Add Reorder Levels</button>
        </div>
      </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-hidden mb-[80px]">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Product</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">AMC</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Lead Time (days)</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Reorder Level</th>
            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="r in props.reorderLevels.data" :key="r.id">
            <td class="px-4 py-2 text-xs">
              <div class="font-medium">{{ r.product?.name }}</div>
              <div class="text-gray-500">ID: {{ r.product?.productID }}</div>
            </td>
            <td class="px-4 py-2 text-xs">{{ format(r.amc) }}</td>
            <td class="px-4 py-2 text-xs">{{ r.lead_time }}</td>
            <td class="px-4 py-2 text-xs">{{ format(r.reorder_level) }}</td>
            <td class="px-4 py-2 text-xs text-right space-x-1">
              <button @click="openEdit(r)" class="p-1 text-blue-600 hover:text-blue-800 rounded" title="Edit">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                  <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h5a1 1 0 010 2H4v10h10v-5a1 1 0 112 0v5a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                </svg>
              </button>
              <button @click="remove(r.id)" class="p-1 text-red-600 hover:text-red-800 rounded" title="Delete">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 100 2h.293l1.213 10.606A2 2 0 007.494 18h5.012a2 2 0 001.988-1.394L15.707 6H16a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0010 2H9zM8 8a1 1 0 112 0v7a1 1 0 11-2 0V8zm4 0a1 1 0 10-2 0v7a1 1 0 102 0V8z" clip-rule="evenodd" />
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showAddModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-lg w-full max-w-6xl p-6">
        <div class="flex items-center justify-between mb-3">
          <div class="font-semibold">{{ isEditing ? 'Edit Reorder Level' : 'Add Reorder Levels' }}</div>
          <button @click="close()" class="text-gray-600">✕</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3"></div>

        <div class="space-y-2 border rounded p-2">
          <div v-for="(row, idx) in rows" :key="idx" class="grid grid-cols-12 gap-2 items-center">
            <div class="col-span-6">
              <Multiselect v-model="row.product" :options="products" track-by="id" label="name" placeholder="Select product" :disabled="isEditing" />
            </div>
            <div class="col-span-2">
              <input v-model.number="row.amc" type="number" min="0" step="0.01" class="w-full border rounded px-2 py-1 text-xs" placeholder="AMC" />
            </div>
            <div class="col-span-2">
              <input v-model.number="row.lead_time" type="number" min="1" class="w-full border rounded px-2 py-1 text-xs" placeholder="Lead" />
            </div>
            <div class="col-span-2 text-right" v-if="!isEditing">
              <button @click="rows.splice(idx,1)" class="px-2 py-1 text-xs bg-gray-200 rounded">Remove</button>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between mt-3">
          <button v-if="!isEditing" @click="addRow" class="px-3 py-1 text-xs bg-gray-100 rounded">Add Row</button>
          <div class="flex items-center gap-2">
            <button @click="close()" class="px-3 py-1 text-sm bg-gray-200 rounded">Cancel</button>
            <button @click="save" :disabled="isSaving || !canSave" class="px-3 py-1 text-sm bg-green-600 text-white rounded">
              {{ isSaving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Import Modal -->
    <div v-if="showImport" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-lg w-full max-w-xl p-6">
        <div class="flex items-center justify-between mb-3">
          <div class="font-semibold">Bulk Upload Reorder Levels</div>
          <button @click="showImport=false" class="text-gray-600">✕</button>
        </div>

        <p class="text-xs text-gray-600 mb-3">Use the template to prepare your data. Only eligible items for your facility will be accepted.</p>
        <div class="mb-4">
          <a :href="route('inventories.facility-reorder-levels.template')" class="px-3 py-1 text-sm bg-indigo-600 text-white rounded">Download Template</a>
        </div>

        <div class="border rounded p-4 mb-4">
          <input type="file" accept=".xlsx,.xls" @change="onFileChange" class="text-sm" />
        </div>

        <div class="flex items-center justify-end gap-2">
          <button @click="showImport=false" class="px-3 py-1 text-sm bg-gray-200 rounded">Cancel</button>
          <button @click="uploadImport" :disabled="isImporting || !importFile" class="px-3 py-1 text-sm bg-amber-600 text-white rounded">
            {{ isImporting ? 'Uploading...' : 'Upload' }}
          </button>
        </div>
      </div>
    </div>

  </AuthenticatedLayout>
  </template>

<script setup>
import { Head, router, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Multiselect from 'vue-multiselect'
import 'vue-multiselect/dist/vue-multiselect.css'
import '@/Components/multiselect.css'
import axios from 'axios'
import Swal from 'sweetalert2'
import { computed, ref, watch, onMounted } from 'vue'

const props = defineProps({
  reorderLevels: Object,
  filters: Object
})

const search = ref(props.filters?.search || '')
const showAddModal = ref(false)
const rows = ref([{ product: null, amc: 0, lead_time: 1 }])
const products = ref([])
const isSaving = ref(false)
const isEditing = ref(false)
const editingId = ref(null)
const showImport = ref(false)
const importFile = ref(null)
const isImporting = ref(false)

onMounted(() => { loadProducts() })

function reload() {
  const query = {}
  if (search.value) query.search = search.value
  router.get(route('inventories.facility-reorder-levels.index'), query, { preserveState: true, preserveScroll: true, only: ['reorderLevels','filters'] })
}

function format(v) { return Number(v || 0).toLocaleString() }

function addRow() { rows.value.push({ product: null, amc: 0, lead_time: 1 }) }

function close() { showAddModal.value = false }

const canSave = computed(() => rows.value.every(r => r.product && r.lead_time >= 1 && r.amc >= 0))

async function loadProducts() {
  try {
    const res = await axios.get(route('inventory.template-products'))
    products.value = res.data?.products || []
  } catch (e) {
    products.value = []
  }
}

async function save() {
  if (!canSave.value) return
  isSaving.value = true
  try {
    if (isEditing.value && editingId.value) {
      // Update single record (fallback to POST with method spoofing to avoid route name cache issues)
      const r = rows.value[0]
      await axios.post(`/inventories/reorder-levels/${editingId.value}`, {
        _method: 'PUT',
        product_id: r.product.id,
        amc: r.amc,
        lead_time: r.lead_time
      })
    } else {
      const payload = {
        items: rows.value.map(r => ({ product_id: r.product.id, amc: r.amc, lead_time: r.lead_time }))
      }
      await axios.post(route('inventories.facility-reorder-levels.store'), payload)
    }
    showAddModal.value = false
    rows.value = [{ product: null, amc: 0, lead_time: 1 }]
    isEditing.value = false
    editingId.value = null
    reload()
  } finally {
    isSaving.value = false
  }
}

async function remove(id) {
  const result = await Swal.fire({
    title: 'Delete Reorder Level?',
    text: 'This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280'
  })

  if (!result.isConfirmed) return

  try {
    await axios.post(`/inventories/reorder-levels/${id}`, { _method: 'DELETE' })
    Swal.fire({ title: 'Deleted!', text: 'Reorder level deleted.', icon: 'success', timer: 1500, showConfirmButton: false })
    reload()
  } catch (e) {
    Swal.fire({ title: 'Failed', text: 'Failed to delete reorder level.', icon: 'error' })
  }
}

function openEdit(record) {
  isEditing.value = true
  editingId.value = record.id
  showAddModal.value = true
  // seed single row
  rows.value = [{
    product: { id: record.product?.id, name: record.product?.name },
    amc: Number(record.amc || 0),
    lead_time: Number(record.lead_time || 1)
  }]
}

function openImportModal() {
  showImport.value = true
}

function onFileChange(e) {
  importFile.value = e.target.files[0] || null
}

async function uploadImport() {
  if (!importFile.value) {
    Swal.fire({ title: 'No file', text: 'Please choose an Excel file first.', icon: 'warning' })
    return
  }
  isImporting.value = true
  try {
    const form = new FormData()
    form.append('file', importFile.value)
    await axios.post(route('inventories.facility-reorder-levels.import'), form, { headers: { 'Content-Type': 'multipart/form-data' } })
    Swal.fire({ title: 'Imported', text: 'Reorder levels imported successfully.', icon: 'success', timer: 1500, showConfirmButton: false })
    showImport.value = false
    importFile.value = null
    reload()
  } catch (e) {
    Swal.fire({ title: 'Failed', text: e.response?.data?.message || 'Import failed.', icon: 'error' })
  } finally {
    isImporting.value = false
  }
}
</script>

<style scoped>
</style>


