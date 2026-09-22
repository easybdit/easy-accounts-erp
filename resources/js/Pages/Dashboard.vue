<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import StatCard from '@/Components/StatCard.vue';
import ModuleFlowDiagram from '@/Components/ModuleFlowDiagram.vue';
import IncomeExpenseChart from '@/Components/IncomeExpenseChart.vue';
import { icons } from '@/icons';

const props = defineProps({
    kpis: Object,
    trend: Array,
});

const isProfit = parseFloat(props.kpis.monthProfit) >= 0;
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="[{ label: 'Dashboard' }]">
        <template #header>
            <PageHeader title="Dashboard" />
        </template>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <StatCard label="Bank Balance" :value="kpis.bankBalance" tone="neutral" :icon="icons.bank" />
            <StatCard label="Income (This Month)" :value="kpis.monthIncome" tone="positive" :icon="icons.arrowTrendingUp" />
            <StatCard label="Expenses (This Month)" :value="kpis.monthExpense" tone="negative" :icon="icons.card" />
            <StatCard
                label="Net Profit (This Month)"
                :value="kpis.monthProfit"
                :tone="isProfit ? 'positive' : 'negative'"
                :icon="icons.scale"
            />
            <StatCard label="Receivable (AR)" :value="kpis.arOutstanding" tone="neutral" :icon="icons.cart" />
            <StatCard label="Payable (AP)" :value="kpis.apOutstanding" tone="neutral" :icon="icons.bag" />
        </div>

        <Card padded class="mt-6">
            <h2 class="mb-4 text-sm font-semibold text-gray-700">Income vs Expense (Last 6 Months)</h2>
            <IncomeExpenseChart :trend="trend" />
        </Card>

        <Card padded class="mt-6">
            <h2 class="mb-1 text-sm font-semibold text-gray-700">How the modules connect</h2>
            <p class="mb-4 text-xs text-gray-400">
                Sales and Purchases both settle through your Bank Accounts, and every transaction posts to the Journal, feeding the
                Ledger and Reports — click any box to open that module.
            </p>
            <ModuleFlowDiagram />
        </Card>
    </AppLayout>
</template>
