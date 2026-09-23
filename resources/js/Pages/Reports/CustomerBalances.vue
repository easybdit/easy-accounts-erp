<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ExportCsvButton from '@/Components/ExportCsvButton.vue';

const props = defineProps({
    rows: Array,
    total: String,
});
</script>

<template>
    <Head title="Customer Balances" />

    <AppLayout :breadcrumbs="[{ label: 'Reports', href: route('reports.index') }, { label: 'Customer Balances' }]">
        <template #header>
            <PageHeader title="Customer Balances">
                <template #actions>
                    <ExportCsvButton route-name="reports.customer-balances" />
                </template>
            </PageHeader>
        </template>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Customer</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="row in rows" :key="row.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                            <Link :href="route('customers.show', row.id)" class="text-indigo-600 hover:text-indigo-900">{{ row.name }}</Link>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-800">{{ row.balance }}</td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td colspan="2" class="px-4 py-6 text-center text-sm text-gray-500">No customers found.</td>
                    </tr>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr class="font-semibold">
                        <td class="px-4 py-3 text-right text-sm text-gray-700">Total</td>
                        <td class="px-4 py-3 text-right text-sm text-gray-900">{{ total }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </AppLayout>
</template>
