<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    budgets: Array,
    comparison: Object,
    filters: Object,
});

const budgetId = ref(props.filters.budget_id ?? '');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');

function apply() {
    router.get(
        route('reports.budget-vs-actual'),
        {
            budget_id: budgetId.value || undefined,
            from: from.value || undefined,
            to: to.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}

watch(budgetId, apply);

function varianceClass(row) {
    // For expenses, actual over budget is unfavorable; for income, actual
    // under budget is unfavorable — so the "good" sign flips by type.
    const favorable = row.account.type === 'expense' ? Number(row.variance) <= 0 : Number(row.variance) >= 0;
    return favorable ? 'text-green-600' : 'text-red-600';
}
</script>

<template>
    <Head title="Budget vs Actual" />

    <AppLayout :breadcrumbs="[{ label: 'Reports', href: route('reports.index') }, { label: 'Budget vs Actual' }]">
        <template #header>
            <PageHeader title="Budget vs Actual" />
        </template>

        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
            <div>
                <label class="block text-xs font-medium text-gray-500">Budget</label>
                <select
                    v-model="budgetId"
                    class="mt-1 block w-64 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">Select a budget</option>
                    <option v-for="budget in budgets" :key="budget.id" :value="budget.id">
                        {{ budget.name }} ({{ budget.fiscal_year }})
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">From</label>
                <input
                    v-model="from"
                    type="date"
                    class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    @change="apply"
                />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">To</label>
                <input
                    v-model="to"
                    type="date"
                    class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    @change="apply"
                />
            </div>
        </div>

        <div v-if="!comparison" class="rounded-lg bg-white p-6 text-center text-sm text-gray-500 shadow-sm">
            Select a budget to compare it against actual activity.
        </div>

        <div v-else class="overflow-hidden rounded-lg bg-white shadow-sm">
            <p class="border-b border-gray-100 px-4 py-3 text-xs text-gray-400">
                The budgeted amount below is each account's annual figure, prorated to the selected date range's
                share of the fiscal year.
            </p>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Account</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Budgeted</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actual</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Variance</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Variance %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="row in comparison.rows" :key="row.account.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                            {{ row.account.code }} — {{ row.account.name }}
                            <span class="text-xs capitalize text-gray-400">({{ row.account.type }})</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ row.budgeted }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ row.actual }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium" :class="varianceClass(row)">
                            {{ row.variance }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium" :class="varianceClass(row)">
                            {{ row.variance_percent === null ? '—' : `${row.variance_percent}%` }}
                        </td>
                    </tr>
                    <tr v-if="comparison.rows.length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                            This budget has no lines.
                        </td>
                    </tr>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td class="px-4 py-3 text-right text-sm font-medium text-gray-700">Total</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ comparison.totalBudgeted }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ comparison.totalActual }}</td>
                        <td colspan="2" />
                    </tr>
                </tfoot>
            </table>
        </div>

        <p class="mt-4 text-sm">
            <Link :href="route('accounting.budgets.index')" class="text-indigo-600 hover:text-indigo-900">Manage Budgets</Link>
        </p>
    </AppLayout>
</template>
