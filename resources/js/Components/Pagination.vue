<template>
    <div v-if="links && Object.keys(links).length > 0" class="flex flex-wrap justify-end gap-2">
        <!-- First Page -->
        <Link
            v-if="links.first"
            :href="links.first"
            class="px-3 py-2 text-sm border rounded-full transition-all duration-200 ease-in-out text-dark-700 border-dark-200 hover:bg-dark-50 hover:text-indigo-600"
        >&laquo;</Link>

        <!-- Previous Page -->
        <Link
            v-if="links.prev"
            :href="links.prev"
            class="px-3 py-2 text-sm border rounded-full transition-all duration-200 ease-in-out text-dark-700 border-dark-200 hover:bg-dark-50 hover:text-indigo-600"
        >&lsaquo;</Link>

        <!-- Current Page (Disabled) -->
        <div
            class="px-3 py-2 text-sm text-white bg-indigo-600 border border-indigo-600 rounded-full"
        >Current</div>

        <!-- Next Page -->
        <Link
            v-if="links.next"
            :href="links.next"
            class="px-3 py-2 text-sm border rounded-full transition-all duration-200 ease-in-out text-dark-700 border-dark-200 hover:bg-dark-50 hover:text-indigo-600"
        >&rsaquo;</Link>

        <!-- Last Page -->
        <Link
            v-if="links.last"
            :href="links.last"
            class="px-3 py-2 text-sm border rounded-full transition-all duration-200 ease-in-out text-dark-700 border-dark-200 hover:bg-dark-50 hover:text-indigo-600"
        >&raquo;</Link>
    </div>
</template>

<script setup>
import { router, Link } from '@inertiajs/vue3';

const props = defineProps({
    links: {
        type: Object,
        required: true,
        default: () => ({})
    }
});
</script>
