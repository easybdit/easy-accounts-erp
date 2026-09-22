<script setup>
defineProps({
    padded: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="overflow-hidden rounded-lg bg-white shadow-sm" :class="padded ? 'p-6' : ''">
        <slot />
    </div>
</template>
