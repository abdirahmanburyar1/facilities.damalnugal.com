<template>
    <div v-if="visible" class="splash-screen">
        <div class="loading-container">
            <img 
                src="/assets/images/loading.gif" 
                alt="Loading" 
                class="loading-image"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    duration: {
        type: Number,
        default: 3500 // 3.5 seconds
    }
});

const emit = defineEmits(['complete']);

const visible = ref(true);

onMounted(() => {
    // Complete after duration
    setTimeout(() => {
        visible.value = false;
        emit('complete');
    }, props.duration);
});
</script>

<style scoped>
.splash-screen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: white;
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-container {
    text-align: center;
}

.loading-image {
    width: 120px;
    height: 120px;
    object-fit: contain;
}

/* Responsive design */
@media (max-width: 768px) {
    .loading-image {
        width: 100px;
        height: 100px;
    }
}

@media (max-width: 480px) {
    .loading-image {
        width: 80px;
        height: 80px;
    }
}
</style>
