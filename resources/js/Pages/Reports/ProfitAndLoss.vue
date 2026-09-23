<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ExportCsvButton from '@/Components/ExportCsvButton.vue';

const props = defineProps({
    income: Array,
    expense: Array,
    totalIncome: String,
    totalExpense: String,
    netProfit: String,
    from: String,
    to: String,
});

const from = ref(props.from ?? '');
const to = ref(props.to ?? '');

function apply() {
    router.get(
        route('reports.profit-and-loss'),
        { from: from.value || undefined, to: to.value || undefined },
        { preserveState: true, replace: true }
    );
}
</script>

<template>
    <Head title="Profit & Loss" />

    <AppLayout :breadcrumbs="[{ label: 'Reports', href: route('reports.index') }, { label: 'Profit & Loss' }]">
        <template #header>
            <PageHeader title="Profit & Loss">
                <template #actions>
                    <ExportCsvButton route-name="reports.profit-and-loss" :params="{ from, to }" />
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
        </div>

        <div class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-2 text-sm font-semibold text-gray-700">Income</h2>
            <table class="mb-4 min-w-full divide-y divide-gray-100">
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="row in income" :key="row.id">
                        <td class="py-2 text-sm text-gray-700">{{ row.code }} — {{ row.name }}</td>
                        <td class="py-2 text-right text-sm text-gray-700">{{ row.amount }}</td>
                    </tr>
                    <tr v-if="income.length === 0">
                        <td colspan="2" class="py-2 text-center text-sm text-gray-400">No income in this period.</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="font-medium">
                        <td class="py-2 text-sm text-gray-700">Total Income</td>
                        <td class="py-2 text-right text-sm text-gray-900">{{ totalIncome }}</td>
                    </tr>
                </tfoot>
            </table>

            <h2 class="mb-2 text-sm font-semibold text-gray-700">Expenses</h2>
            <table class="mb-4 min-w-full divide-y divide-gray-100">
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="row in expense" :key="row.id">
                        <td class="py-2 text-sm text-gray-700">{{ row.code }} — {{ row.name }}</td>
                        <td class="py-2 text-right text-sm text-gray-700">{{ row.amount }}</td>
                    </tr>
                    <tr v-if="expense.length === 0">
                        <td colspan="2" class="py-2 text-center text-sm text-gray-400">No expenses in this period.</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="font-medium">
                        <td class="py-2 text-sm text-gray-700">Total Expenses</td>
                        <td class="py-2 text-right text-sm text-gray-900">{{ totalExpense }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="flex justify-between border-t border-gray-200 pt-4 text-base font-semibold">
                <span>Net Profit</span>
                <span :class="parseFloat(netProfit) >= 0 ? 'text-green-700' : 'text-red-700'">{{ netProfit }}</span>
            </div>
        </div>
    </AppLayout>
</template>
