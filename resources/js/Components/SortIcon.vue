<template>
    <div class="ml-1 flex flex-col">
        <svg
            :class="[
                'w-3 h-3 transition-opacity',
                field === currentSort && direction === 'asc'
                    ? 'text-blue-500'
                    : 'text-gray-400 opacity-50'
            ]"
            fill="currentColor"
            viewBox="0 0 20 20"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                fill-rule="evenodd"
                d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                clip-rule="evenodd"
            ></path>
        </svg>
        <svg
            :class="[
                'w-3 h-3 transition-opacity',
                field === currentSort && direction === 'desc'
                    ? 'text-blue-500'
                    : 'text-gray-400 opacity-50'
            ]"
            fill="currentColor"
            viewBox="0 0 20 20"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                fill-rule="evenodd"
                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                clip-rule="evenodd"
            ></path>
        </svg>
    </div>
</template>

<script setup>
defineProps({
    field: String,
    currentSort: String,
    direction: String
});
</script>
