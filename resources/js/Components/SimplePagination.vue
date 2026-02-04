&lt;template&gt;
    &lt;div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6"&gt;
        &lt;div class="flex flex-1 justify-between sm:hidden"&gt;
            &lt;button
                @click="prevPage"
                :disabled="!hasPrevPage"
                :class="[
                    'relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700',
                    hasPrevPage ? 'hover:bg-gray-50' : 'opacity-50 cursor-not-allowed'
                ]"
            &gt;
                Previous
            &lt;/button&gt;
            &lt;button
                @click="nextPage"
                :disabled="!hasNextPage"
                :class="[
                    'relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700',
                    hasNextPage ? 'hover:bg-gray-50' : 'opacity-50 cursor-not-allowed'
                ]"
            &gt;
                Next
            &lt;/button&gt;
        &lt;/div&gt;
        &lt;div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between"&gt;
            &lt;div&gt;
                &lt;p class="text-sm text-gray-700"&gt;
                    Showing
                    &lt;span class="font-medium"&gt;{{ meta.from }}&lt;/span&gt;
                    to
                    &lt;span class="font-medium"&gt;{{ meta.to }}&lt;/span&gt;
                    of
                    &lt;span class="font-medium"&gt;{{ meta.total }}&lt;/span&gt;
                    results
                &lt;/p&gt;
            &lt;/div&gt;
            &lt;div&gt;
                &lt;nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination"&gt;
                    &lt;button
                        @click="prevPage"
                        :disabled="!hasPrevPage"
                        :class="[
                            'relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300',
                            hasPrevPage ? 'hover:bg-gray-50' : 'opacity-50 cursor-not-allowed'
                        ]"
                    &gt;
                        &lt;span class="sr-only"&gt;Previous&lt;/span&gt;
                        &lt;svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"&gt;
                            &lt;path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /&gt;
                        &lt;/svg&gt;
                    &lt;/button&gt;
                    &lt;button
                        v-for="page in visiblePages"
                        :key="page"
                        @click="goToPage(page)"
                        :class="[
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold',
                            page === meta.current_page
                                ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                                : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-offset-0',
                        ]"
                    &gt;
                        {{ page }}
                    &lt;/button&gt;
                    &lt;button
                        @click="nextPage"
                        :disabled="!hasNextPage"
                        :class="[
                            'relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300',
                            hasNextPage ? 'hover:bg-gray-50' : 'opacity-50 cursor-not-allowed'
                        ]"
                    &gt;
                        &lt;span class="sr-only"&gt;Next&lt;/span&gt;
                        &lt;svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"&gt;
                            &lt;path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /&gt;
                        &lt;/svg&gt;
                    &lt;/button&gt;
                &lt;/nav&gt;
            &lt;/div&gt;
        &lt;/div&gt;
    &lt;/div&gt;
&lt;/template&gt;

&lt;script setup&gt;
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    meta: {
        type: Object,
        required: true
    }
});

const hasNextPage = computed(() => props.meta.current_page < props.meta.last_page);
const hasPrevPage = computed(() => props.meta.current_page > 1);

const visiblePages = computed(() => {
    const current = props.meta.current_page;
    const last = props.meta.last_page;
    const delta = 2;
    const range = [];
    
    for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        range.push(i);
    }
    
    if (current - delta > 2) {
        range.unshift('...');
    }
    if (current + delta < last - 1) {
        range.push('...');
    }
    
    range.unshift(1);
    if (last > 1) {
        range.push(last);
    }
    
    return range;
});

const updatePage = (page) => {
    const currentQuery = new URLSearchParams(window.location.search);
    currentQuery.set('page', page);
    
    router.get(`${window.location.pathname}?${currentQuery.toString()}`, {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['purchase_orders', 'filters']
    });
};

const prevPage = () => {
    if (hasPrevPage.value) {
        updatePage(props.meta.current_page - 1);
    }
};

const nextPage = () => {
    if (hasNextPage.value) {
        updatePage(props.meta.current_page + 1);
    }
};

const goToPage = (page) => {
    if (page !== '...') {
        updatePage(page);
    }
};
&lt;/script&gt;
