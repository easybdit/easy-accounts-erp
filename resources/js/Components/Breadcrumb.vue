<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-1 text-sm text-gray-500">
            <li v-for="(item, index) in items" :key="index" class="flex items-center gap-1">
                <span v-if="index > 0" class="text-gray-300">/</span>
                <Link
                    v-if="item.href && index < items.length - 1"
                    :href="item.href"
                    class="hover:text-gray-700"
                >
                    {{ item.label }}
                </Link>
                <span v-else class="font-medium text-gray-700">{{ item.label }}</span>
            </li>
        </ol>
    </nav>
</template>
