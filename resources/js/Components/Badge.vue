<script setup>
defineProps({
    variant: {
        type: String,
        default: 'neutral',
        validator: (value) => ['success', 'danger', 'warning', 'info', 'neutral'].includes(value),
    },
});

const variants = {
    success: 'bg-green-100 text-green-700',
    danger: 'bg-red-100 text-red-700',
    warning: 'bg-amber-100 text-amber-700',
    info: 'bg-indigo-100 text-indigo-700',
    neutral: 'bg-gray-100 text-gray-600',
};
</script>

<template>
    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium" :class="variants[variant]">
        <slot />
    </span>
</template>
