<script setup>
import { onMounted, ref, computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        required: true
    },
    type: {
        type: String,
        default: 'text'
    }
});

const emit = defineEmits(['update:modelValue']);

const input = ref(null);

const value = computed({
    get: () => props.modelValue,
    set: (val) => {
        if (props.type === 'number') {
            emit('update:modelValue', Number(val));
        } else {
            emit('update:modelValue', String(val));
        }
    }
});

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        :type="type"
        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        :value="value"
        @input="value = $event.target.value"
        ref="input"
    />
</template>
