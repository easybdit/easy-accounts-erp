<script setup>
import { computed } from 'vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    routeName: {
        type: String,
        required: true,
    },
    params: {
        type: Object,
        default: () => ({}),
    },
});

const href = computed(() => route(props.routeName, { ...props.params, export: 1 }));
</script>

<template>
    <a :href="href">
        <SecondaryButton type="button">Export CSV</SecondaryButton>
    </a>
</template>
