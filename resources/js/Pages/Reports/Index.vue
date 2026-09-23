<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';

const groups = [
    {
        label: 'Financial Statements',
        reports: [
            { name: 'Profit & Loss', routeName: 'reports.profit-and-loss' },
            { name: 'Balance Sheet', routeName: 'reports.balance-sheet' },
            { name: 'Cash Flow Statement', routeName: 'reports.cash-flow-statement' },
            { name: 'Cash Flow (movement summary)', routeName: 'reports.cash-flow' },
            { name: 'Trial Balance', routeName: 'accounting.trial-balance.index' },
        ],
    },
    {
        label: 'Receivables & Payables',
        reports: [
            { name: 'Accounts Receivable Aging', routeName: 'reports.ar-aging' },
            { name: 'Accounts Payable Aging', routeName: 'reports.ap-aging' },
            { name: 'Customer Balances', routeName: 'reports.customer-balances' },
            { name: 'Customer Statement', routeName: 'reports.customer-statement' },
            { name: 'Vendor Balances', routeName: 'reports.vendor-balances' },
        ],
    },
    {
        label: 'Activity',
        reports: [
            { name: 'Sales Report', routeName: 'reports.sales' },
            { name: 'Purchase Report', routeName: 'reports.purchases' },
            { name: 'Expense Report', routeName: 'reports.expenses' },
            { name: 'Payments Report', routeName: 'reports.payments' },
        ],
    },
    {
        label: 'Other',
        reports: [
            { name: 'General Ledger', routeName: 'accounting.ledger.index' },
            { name: 'Tax Report', routeName: 'tax.report' },
            { name: 'Inventory Report', routeName: 'reports.inventory' },
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

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <Card v-for="group in groups" :key="group.label" padded>
                <h2 class="mb-3 text-sm font-semibold text-gray-700">{{ group.label }}</h2>
                <ul class="space-y-2">
                    <li v-for="report in group.reports" :key="report.name">
                        <Link :href="route(report.routeName)" class="text-sm text-indigo-600 hover:text-indigo-900">
                            {{ report.name }}
                        </Link>
                    </li>
                </ul>
            </Card>
        </div>
    </AppLayout>
</template>
