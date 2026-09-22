<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: String,
        required: true,
    },
    tone: {
        type: String,
        default: 'neutral',
        validator: (value) => ['neutral', 'positive', 'negative'].includes(value),
    },
    icon: {
        type: Array,
        required: true,
    },
});

const toneClasses = {
    neutral: 'bg-indigo-50 text-indigo-600',
    positive: 'bg-green-50 text-green-600',
    negative: 'bg-red-50 text-red-600',
};

const valueClasses = {
    neutral: 'text-gray-900',
    positive: 'text-green-700',
    negative: 'text-red-700',
};
</script>

<template>
    <div class="rounded-lg bg-white p-5 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" :class="toneClasses[tone]">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path v-for="(d, i) in icon" :key="i" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="d" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-gray-400">{{ label }}</p>
                <p class="text-lg font-semibold" :class="valueClasses[tone]">{{ value }}</p>
            </div>
        </div>
    </div>
</template>
