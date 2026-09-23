<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ExportCsvButton from '@/Components/ExportCsvButton.vue';

const props = defineProps({
    rows: Array,
    totals: Object,
    asOf: String,
});

const asOf = ref(props.asOf);

function apply() {
    router.get(route('reports.ap-aging'), { as_of: asOf.value || undefined }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Accounts Payable Aging" />

    <AppLayout :breadcrumbs="[{ label: 'Reports', href: route('reports.index') }, { label: 'AP Aging' }]">
        <template #header>
            <PageHeader title="Accounts Payable Aging">
                <template #actions>
                    <ExportCsvButton route-name="reports.ap-aging" :params="{ as_of: asOf }" />
                </template>
            </PageHeader>
        </template>

        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
            <div>
                <label class="block text-xs font-medium text-gray-500">As of</label>
                <input v-model="asOf" type="date" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="apply" />
            </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Vendor</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Current</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">1-30</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">31-60</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">61-90</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">90+</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="row in rows" :key="row.id">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                <Link :href="route('vendors.show', row.id)" class="text-indigo-600 hover:text-indigo-900">{{ row.name }}</Link>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ row.current }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ row.d1_30 }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ row.d31_60 }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ row.d61_90 }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ row.d90_plus }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-800">{{ row.total }}</td>
                        </tr>
                        <tr v-if="rows.length === 0">
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">No outstanding payables.</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr class="font-semibold">
                            <td class="px-4 py-3 text-right text-sm text-gray-700">Total</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-900">{{ totals.current }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-900">{{ totals.d1_30 }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-900">{{ totals.d31_60 }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-900">{{ totals.d61_90 }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-900">{{ totals.d90_plus }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-900">{{ totals.total }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
