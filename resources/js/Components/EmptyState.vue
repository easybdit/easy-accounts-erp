<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center px-4 py-12 text-center">
        <svg class="mb-3 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0l-2.5 5a2 2 0 01-1.789 1.1H8.289A2 2 0 016.5 18L4 13m16 0h-3.5a1 1 0 00-.949.684l-.5 1.5a1 1 0 01-.949.684H9.898a1 1 0 01-.949-.684l-.5-1.5A1 1 0 007.5 13H4"
            />
        </svg>
        <p class="text-sm font-medium text-gray-700">{{ title }}</p>
        <p v-if="$slots.default || description" class="mt-1 max-w-sm text-sm text-gray-400">
            <slot>{{ description }}</slot>
        </p>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
