<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center sm:justify-center sm:pt-0"
        style="background-image: url('/assets/images/bg.jpeg'); background-size: cover; background-position: center top; background-repeat: no-repeat;"
    >

        <div
            class="mt-6 min-w-[250px] w-full max-w-2xl overflow-hidden shadow-md sm:rounded-lg">
            <slot />
        </div>
        <!-- <div class="flex justify-between gap-2 w-full ">
            <img src="/assets/images/psi.jpg" class="h-20 w-20 fill-current text-gray-500" />
            <img src="/assets/images/moh.png" class="h-20 w-20 fill-cover rounded-full text-gray-500" />
        </div> -->
    </div>
</template>
