<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="[{ label: 'Dashboard' }]">
        <template #header>
            <PageHeader title="Dashboard" />
        </template>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <div class="p-6 text-gray-900">You're logged in!</div>
        </div>
    </AppLayout>
</template>
