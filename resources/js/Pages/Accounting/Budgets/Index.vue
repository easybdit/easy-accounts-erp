<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    budgets: Array,
});

function destroy(budget) {
    if (confirm(`Delete the budget "${budget.name}"?`)) {
        router.delete(route('accounting.budgets.destroy', budget.id));
    }
}
</script>

<template>
    <Head title="Budgets" />

    <AppLayout :breadcrumbs="[{ label: 'Accounting' }, { label: 'Budgets' }]">
        <template #header>
            <PageHeader title="Budgets">
                <template #actions>
                    <Link :href="route('reports.budget-vs-actual')">
                        <SecondaryButton type="button">Budget vs Actual</SecondaryButton>
                    </Link>
                    <Link :href="route('accounting.budgets.create')">
                        <PrimaryButton>New Budget</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <Card>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Fiscal Year</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Accounts</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Total Budgeted</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="budget in budgets" :key="budget.id" class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ budget.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ budget.fiscal_year }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-500">{{ budget.lines_count }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ budget.total_amount }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <Link :href="route('accounting.budgets.edit', budget.id)" class="mr-3 text-indigo-600 hover:text-indigo-900">
                                Edit
                            </Link>
                            <button type="button" class="text-red-600 hover:text-red-900" @click="destroy(budget)">
                                Delete
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState
                v-if="budgets.length === 0"
                title="No budgets yet"
                description="Create a budget to compare planned income and expenses against what actually happened."
            />
        </Card>
    </AppLayout>
</template>
