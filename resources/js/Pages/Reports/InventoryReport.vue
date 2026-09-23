<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ExportCsvButton from '@/Components/ExportCsvButton.vue';

const props = defineProps({
    rows: Array,
    totalValue: String,
});
</script>

<template>
    <Head title="Inventory Report" />

    <AppLayout :breadcrumbs="[{ label: 'Reports', href: route('reports.index') }, { label: 'Inventory Report' }]">
        <template #header>
            <PageHeader title="Inventory Report">
                <template #actions>
                    <ExportCsvButton route-name="reports.inventory" />
                </template>
            </PageHeader>
        </template>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">SKU</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Category</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Stock</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Unit Cost</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Stock Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="row in rows" :key="row.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ row.sku }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                            <Link :href="route('inventory.products.show', row.id)" class="text-indigo-600 hover:text-indigo-900">{{ row.name }}</Link>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ row.category }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm" :class="row.is_low_stock ? 'font-semibold text-red-600' : 'text-gray-700'">
                            {{ row.current_stock }} {{ row.unit }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ row.purchase_price }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-800">{{ row.stock_value }}</td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">No inventory-tracked products found.</td>
                    </tr>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr class="font-semibold">
                        <td class="px-4 py-3 text-right text-sm text-gray-700" colspan="5">Total Stock Value</td>
                        <td class="px-4 py-3 text-right text-sm text-gray-900">{{ totalValue }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </AppLayout>
</template>
