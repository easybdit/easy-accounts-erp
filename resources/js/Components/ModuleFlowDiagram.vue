<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { icons } from '@/icons';

const page = usePage();

function can(permission) {
    return page.props.auth.permissions?.includes(permission) ?? false;
}

const lanes = [
    {
        label: 'Sales',
        nodes: [
            { label: 'Customers', routeName: 'customers.index', permission: 'customers.view', icon: icons.cart },
            { label: 'Invoices', routeName: 'sales.invoices.index', permission: 'invoices.view', icon: icons.document },
            { label: 'Payments', routeName: 'sales.payments.index', permission: 'payments.view', icon: icons.card },
        ],
    },
    {
        label: 'Purchases',
        nodes: [
            { label: 'Vendors', routeName: 'vendors.index', permission: 'vendors.view', icon: icons.bag },
            { label: 'Bills', routeName: 'purchases.bills.index', permission: 'bills.view', icon: icons.document },
            { label: 'Payments', routeName: 'purchases.vendor-payments.index', permission: 'payments.view', icon: icons.card },
        ],
    },
    {
        label: 'Expenses',
        nodes: [
            { label: 'Expenses', routeName: 'expenses.entries.index', permission: 'expenses.view', icon: icons.card },
        ],
    },
];

const hub = [
    { label: 'Bank Accounts', routeName: 'banking.accounts.index', permission: 'banking.view', icon: icons.bank },
    { label: 'Journal', routeName: 'accounting.journals.index', permission: 'journal.view', icon: icons.calculator },
    { label: 'Ledger', routeName: 'accounting.ledger.index', permission: 'accounts.view', icon: icons.calculator },
    { label: 'Reports', routeName: 'reports.index', permission: 'reports.view', icon: icons.chartPie },
];

const visibleLanes = computed(() =>
    lanes
        .map((lane) => ({ ...lane, nodes: lane.nodes.filter((node) => can(node.permission)) }))
        .filter((lane) => lane.nodes.length > 0)
);

const visibleHub = computed(() => hub.filter((node) => can(node.permission)));
</script>

<template>
    <div v-if="visibleLanes.length && visibleHub.length" class="overflow-x-auto">
        <div class="flex min-w-[820px] items-stretch gap-4">
            <!-- Lanes: Sales / Purchases / Expenses -->
            <div class="flex flex-1 flex-col justify-between gap-4">
                <div v-for="lane in visibleLanes" :key="lane.label" class="flex items-center gap-2">
                    <span class="w-20 shrink-0 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ lane.label }}</span>
                    <template v-for="(node, index) in lane.nodes" :key="node.label + index">
                        <Component
                            :is="node.routeName ? Link : 'div'"
                            :href="node.routeName ? route(node.routeName) : undefined"
                            class="flex shrink-0 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700"
                        >
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-for="(d, i) in node.icon" :key="i" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="d" />
                            </svg>
                            {{ node.label }}
                        </Component>
                        <svg v-if="index < lane.nodes.length - 1" class="h-4 w-4 shrink-0 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </template>
                </div>
            </div>

            <!-- Merge connector -->
            <div class="flex flex-col items-center justify-center px-1">
                <div class="h-full w-px bg-gray-200" />
            </div>
            <div class="flex shrink-0 items-center">
                <svg class="h-4 w-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>

            <!-- Hub: Bank -> Journal -> Ledger -> Reports -->
            <div class="flex shrink-0 items-center gap-2">
                <template v-for="(node, index) in visibleHub" :key="node.label">
                    <Link
                        :href="route(node.routeName)"
                        class="flex shrink-0 items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-medium text-indigo-700 shadow-sm transition hover:border-indigo-400 hover:bg-indigo-100"
                    >
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-for="(d, i) in node.icon" :key="i" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="d" />
                        </svg>
                        {{ node.label }}
                    </Link>
                    <svg v-if="index < visibleHub.length - 1" class="h-4 w-4 shrink-0 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </template>
            </div>
        </div>
    </div>
</template>
