<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ExportCsvButton from '@/Components/ExportCsvButton.vue';

const props = defineProps({
    accounts: Array,
    totalOpening: String,
    totalIn: String,
    totalOut: String,
    totalClosing: String,
    from: String,
    to: String,
});

const from = ref(props.from ?? '');
const to = ref(props.to ?? '');

function apply() {
    router.get(
        route('reports.cash-flow'),
        { from: from.value || undefined, to: to.value || undefined },
        { preserveState: true, replace: true }
    );
}
</script>

<template>
    <Head title="Cash Flow" />

    <AppLayout :breadcrumbs="[{ label: 'Reports', href: route('reports.index') }, { label: 'Cash Flow' }]">
        <template #header>
            <PageHeader title="Cash Flow">
                <template #actions>
                    <ExportCsvButton route-name="reports.cash-flow" :params="{ from, to }" />
                </template>
            </PageHeader>
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
                A per-account cash movement summary. For an Operating/Investing/Financing
                breakdown, see the
                <Link :href="route('reports.cash-flow-statement')" class="text-indigo-600 hover:text-indigo-900">Cash Flow Statement</Link>.
            </p>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Account</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Opening</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">In</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Out</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Closing</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="account in accounts" :key="account.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ account.code }} — {{ account.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ account.opening }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-green-700">{{ account.in }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-red-700">{{ account.out }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-800">{{ account.closing }}</td>
                    </tr>
                    <tr v-if="accounts.length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                            No accounts are marked as Bank/Cash accounts yet.
                        </td>
                    </tr>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr class="font-semibold">
                        <td class="px-4 py-3 text-right text-sm text-gray-700">Total</td>
                        <td class="px-4 py-3 text-right text-sm text-gray-900">{{ totalOpening }}</td>
                        <td class="px-4 py-3 text-right text-sm text-gray-900">{{ totalIn }}</td>
                        <td class="px-4 py-3 text-right text-sm text-gray-900">{{ totalOut }}</td>
                        <td class="px-4 py-3 text-right text-sm text-gray-900">{{ totalClosing }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </AppLayout>
</template>
