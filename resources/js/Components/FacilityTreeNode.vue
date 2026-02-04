<template>
    <div class="facility-tree-node">
        <div class="flex items-center space-x-2 py-1">
            <div
                v-if="facility.children && facility.children.length > 0"
                @click="toggleExpanded"
                class="w-4 h-4 flex items-center justify-center cursor-pointer text-gray-500 hover:text-gray-700"
            >
                <svg
                    :class="{ 'rotate-90': isExpanded }"
                    class="w-3 h-3 transition-transform"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
            </div>
            <div v-else class="w-4 h-4"></div>
            
            <label class="flex items-center space-x-2 cursor-pointer flex-1">
                <input
                    type="radio"
                    :value="facility.id"
                    :checked="selectedId === facility.id"
                    @change="$emit('select', facility.id)"
                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 focus:ring-2"
                >
                <span
                    :class="[
                        'text-sm',
                        facility.level === 0 ? 'font-semibold text-blue-900' : 'text-gray-700',
                        facility.type === 'region' ? 'text-blue-800' : '',
                        facility.type === 'district' ? 'text-green-700' : '',
                        facility.type === 'facility' ? 'text-gray-600' : ''
                    ]"
                >
                    <span v-if="facility.type === 'region'" class="mr-1">🏛️</span>
                    <span v-else-if="facility.type === 'district'" class="mr-1">🏢</span>
                    <span v-else-if="facility.type === 'facility'" class="mr-1">🏥</span>
                    {{ facility.name }}
                    <span v-if="facility.type" class="text-xs text-gray-500 ml-1">({{ facility.type }})</span>
                </span>
            </label>
        </div>
        
        <div
            v-if="facility.children && facility.children.length > 0 && isExpanded"
            class="ml-6 space-y-1 border-l border-gray-200 pl-4"
        >
            <FacilityTreeNode
                v-for="child in facility.children"
                :key="child.id"
                :facility="child"
                :selectedId="selectedId"
                @select="$emit('select', $event)"
            />
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
    facility: Object,
    selectedId: [Number, String]
})

defineEmits(['select'])

const isExpanded = ref(true) // Start expanded by default

const toggleExpanded = () => {
    isExpanded.value = !isExpanded.value
}
</script>
