<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import { icons } from '@/icons';

// bg-50/text-600 pairs, one per group, echoing the tone convention StatCard
// uses on the Dashboard so this page reads as part of the same design system.
const colors = {
    indigo: 'bg-indigo-50 text-indigo-600',
    amber: 'bg-amber-50 text-amber-600',
    emerald: 'bg-emerald-50 text-emerald-600',
    violet: 'bg-violet-50 text-violet-600',
};

const groups = [
    {
        label: 'Financial Statements',
        description: 'The core statements that summarize the business’s financial position and performance.',
        icon: icons.calculator,
        color: 'indigo',
        reports: [
            { name: 'Profit & Loss', description: 'Income, expenses, and net profit for a period', routeName: 'reports.profit-and-loss', icon: icons.arrowTrendingUp },
            { name: 'Balance Sheet', description: 'Assets, liabilities, and equity as of a date', routeName: 'reports.balance-sheet', icon: icons.scale },
            { name: 'Cash Flow Statement', description: 'Cash movement by operating, investing, and financing activity', routeName: 'reports.cash-flow-statement', icon: icons.bank },
            { name: 'Cash Flow (movement summary)', description: 'A simplified summary of cash in vs. cash out', routeName: 'reports.cash-flow', icon: icons.arrowTrendingUp },
            { name: 'Trial Balance', description: 'Debit and credit balance of every account', routeName: 'accounting.trial-balance.index', icon: icons.calculator },
        ],
    },
    {
        label: 'Receivables & Payables',
        description: 'Who owes the business money, and who the business owes.',
        icon: icons.scale,
        color: 'amber',
        reports: [
            { name: 'Accounts Receivable Aging', description: 'How long customer balances have been outstanding', routeName: 'reports.ar-aging', icon: icons.cart },
            { name: 'Accounts Payable Aging', description: 'How long vendor balances have been outstanding', routeName: 'reports.ap-aging', icon: icons.bag },
            { name: 'Customer Balances', description: 'Current balance owed by every customer', routeName: 'reports.customer-balances', icon: icons.cart },
            { name: 'Customer Statement', description: 'Full transaction history for one customer', routeName: 'reports.customer-statement', icon: icons.document },
            { name: 'Vendor Balances', description: 'Current balance owed to every vendor', routeName: 'reports.vendor-balances', icon: icons.bag },
            { name: 'Vendor Statement', description: 'Full transaction history for one vendor', routeName: 'reports.vendor-statement', icon: icons.document },
        ],
    },
    {
        label: 'Activity',
        description: 'What moved through the business over a period.',
        icon: icons.chartPie,
        color: 'emerald',
        reports: [
            { name: 'Sales Report', description: 'Summary of all sales for a period', routeName: 'reports.sales', icon: icons.cart },
            { name: 'Purchase Report', description: 'Summary of all purchases for a period', routeName: 'reports.purchases', icon: icons.bag },
            { name: 'Expense Report', description: 'Expenses broken down by category', routeName: 'reports.expenses', icon: icons.card },
            { name: 'Payments Report', description: 'All payments received and made', routeName: 'reports.payments', icon: icons.bank },
        ],
    },
    {
        label: 'Other',
        description: 'Ledger detail, tax, inventory, and budget tracking.',
        icon: icons.document,
        color: 'violet',
        reports: [
            { name: 'General Ledger', description: 'Every transaction for a chosen account', routeName: 'accounting.ledger.index', icon: icons.document },
            { name: 'Tax Report', description: 'Tax collected and withheld for a period', routeName: 'tax.report', icon: icons.scale },
            { name: 'Inventory Report', description: 'Stock levels, valuation, and movement', routeName: 'reports.inventory', icon: icons.cube },
            { name: 'Budget vs Actual', description: 'Budgeted targets compared to actual results', routeName: 'reports.budget-vs-actual', icon: icons.chartPie },
        ],
    },
];
</script>

<template>
    <Head title="Reports" />

    <AppLayout :breadcrumbs="[{ label: 'Reports' }]">
        <template #header>
            <PageHeader title="Reports" />
        </template>

        <p class="-mt-2 mb-6 text-sm text-gray-500">
            Every report below reflects your live data and can be filtered and exported to CSV once opened.
        </p>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <Card v-for="group in groups" :key="group.label" padded>
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" :class="colors[group.color]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-for="(d, i) in group.icon" :key="i" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="d" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">{{ group.label }}</h2>
                        <p class="text-xs text-gray-400">{{ group.description }}</p>
                    </div>
                </div>

                <ul class="divide-y divide-gray-100">
                    <li v-for="report in group.reports" :key="report.name">
                        <Link
                            :href="route(report.routeName)"
                            class="group flex items-center gap-3 rounded-md px-2 py-3 transition hover:bg-gray-50"
                        >
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition group-hover:bg-white group-hover:text-indigo-600 group-hover:shadow-sm">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-for="(d, i) in report.icon" :key="i" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="d" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-800 group-hover:text-indigo-700">{{ report.name }}</p>
                                <p class="truncate text-xs text-gray-400">{{ report.description }}</p>
                            </div>
                            <svg class="h-4 w-4 shrink-0 text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </li>
                </ul>
            </Card>
        </div>
    </AppLayout>
</template>
