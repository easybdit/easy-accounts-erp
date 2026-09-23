<script setup>
import { usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const page = usePage();
</script>

<template>
    <img v-if="page.props.company?.logoUrl" :src="page.props.company.logoUrl" alt="Company logo" class="object-contain" />
    <ApplicationLogo v-else />
</template>
