<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: {
        type: String,
        required: true,
    },
    photoUrl: {
        type: String,
        default: null,
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
});

const sizeClasses = {
    sm: 'h-8 w-8 text-xs',
    md: 'h-10 w-10 text-sm',
    lg: 'h-20 w-20 text-2xl',
};

// First letter of up to the first two words — "Md Murad Hosen" -> "MM".
const initials = computed(() =>
    props.name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((word) => word[0]?.toUpperCase())
        .join('') || '?'
);
</script>

<template>
    <img
        v-if="photoUrl"
        :src="photoUrl"
        :alt="name"
        class="shrink-0 rounded-full object-cover"
        :class="sizeClasses[size]"
    />
    <span
        v-else
        class="flex shrink-0 items-center justify-center rounded-full bg-indigo-100 font-semibold text-indigo-700"
        :class="sizeClasses[size]"
    >
        {{ initials }}
    </span>
</template>
