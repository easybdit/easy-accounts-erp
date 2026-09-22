<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    operating: Array,
    investing: Array,
    financing: Array,
    totalOperating: String,
    totalInvesting: String,
    totalFinancing: String,
    netChange: String,
    openingCash: String,
    closingCash: String,
    from: String,
    to: String,
});

const from = ref(props.from ?? '');
const to = ref(props.to ?? '');

function apply() {
    router.get(
        route('reports.cash-flow-statement'),
        { from: from.value || undefined, to: to.value || undefined },
        { preserveState: true, replace: true }
    );
}

const sections = [
    { key: 'operating', label: 'Operating Activities', rowsKey: 'operating', totalKey: 'totalOperating' },
    { key: 'investing', label: 'Investing Activities', rowsKey: 'investing', totalKey: 'totalInvesting' },
    { key: 'financing', label: 'Financing Activities', rowsKey: 'financing', totalKey: 'totalFinancing' },
];
</script>

<template>
    <Head title="Cash Flow Statement" />

    <AppLayout :breadcrumbs="[{ label: 'Reports', href: route('reports.index') }, { label: 'Cash Flow Statement' }]">
        <template #header>
            <PageHeader title="Cash Flow Statement" />
        </template>

        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
            <div>
                <label class="block text-xs font-medium text-gray-500">From</label>
                <input v-model="from" type="date" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="apply" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">To</label>
                <input v-model="to" type="date" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="apply" />
            </div>
            <p class="text-xs text-gray-400">
                Direct method: each Bank/Cash account's movement, classified by the
                <Link :href="route('accounting.accounts.index')" class="text-indigo-600 hover:text-indigo-900">Cash Flow Category</Link>
                of the account on the other side of the transaction. Transfers between your own bank accounts are excluded.
            </p>
        </div>

        <div class="space-y-4">
            <div v-for="section in sections" :key="section.key" class="overflow-hidden rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ section.label }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="row in props[section.rowsKey]" :key="row.id">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ row.code }} — {{ row.name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm" :class="row.amount.startsWith('-') ? 'text-red-700' : 'text-green-700'">
                                {{ row.amount }}
                            </td>
                        </tr>
                        <tr v-if="props[section.rowsKey].length === 0">
                            <td colspan="2" class="px-4 py-4 text-center text-sm text-gray-400">No activity in this period.</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr class="font-semibold">
                            <td class="px-4 py-3 text-right text-sm text-gray-700">Net Cash from {{ section.label }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-900">{{ props[section.totalKey] }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <tbody class="divide-y divide-gray-200 text-sm">
                        <tr class="font-semibold">
                            <td class="px-4 py-3 text-gray-700">Net Increase / (Decrease) in Cash</td>
                            <td class="px-4 py-3 text-right text-gray-900">{{ netChange }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-gray-700">Cash at Beginning of Period</td>
                            <td class="px-4 py-3 text-right text-gray-900">{{ openingCash }}</td>
                        </tr>
                        <tr class="font-semibold">
                            <td class="px-4 py-3 text-gray-700">Cash at End of Period</td>
                            <td class="px-4 py-3 text-right text-gray-900">{{ closingCash }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
