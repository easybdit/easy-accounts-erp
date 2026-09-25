<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { icons } from '@/icons';

const page = usePage();

function can(permission) {
    return page.props.auth.permissions?.includes(permission) ?? false;
}

// Every lane ends by posting into the Journal — this list mirrors the
// modules that actually call an accounting-posting action (see
// app/Actions/{Banking,Expenses,Inventory,Payments,Purchases,Sales,Payroll}).
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
    {
        label: 'Inventory',
        nodes: [
            { label: 'Products', routeName: 'inventory.products.index', permission: 'inventory.view', icon: icons.cube },
            { label: 'Stock Movements', routeName: 'inventory.stock-movements.index', permission: 'inventory.view', icon: icons.cube },
        ],
    },
    {
        label: 'Banking',
        nodes: [
            { label: 'Bank Accounts', routeName: 'banking.accounts.index', permission: 'banking.view', icon: icons.bank },
            { label: 'Deposits & Transfers', routeName: 'banking.transfers.index', permission: 'banking.view', icon: icons.bank },
        ],
    },
    {
        label: 'Fixed Assets',
        nodes: [
            { label: 'Depreciation & Disposal', routeName: 'accounting.fixed-assets.index', permission: 'accounts.view', icon: icons.calculator },
        ],
    },
    {
        label: 'HR',
        nodes: [
            { label: 'Employees', routeName: 'hr.employees.index', permission: 'employees.view', icon: icons.users },
            { label: 'Attendance', routeName: 'hr.attendance.index', permission: 'employees.view', icon: icons.users },
            { label: 'Payroll', routeName: 'hr.payroll.index', permission: 'payroll.view', icon: icons.card },
        ],
    },
];

const hub = [
    { label: 'Journal', routeName: 'accounting.journals.index', permission: 'journal.view', icon: icons.calculator },
    { label: 'General Ledger', routeName: 'accounting.ledger.index', permission: 'accounts.view', icon: icons.calculator },
    { label: 'Trial Balance', routeName: 'accounting.trial-balance.index', permission: 'accounts.view', icon: icons.calculator },
    { label: 'Reports', routeName: 'reports.index', permission: 'reports.view', icon: icons.chartPie },
];

// Relations that don't fit the straight "posts to the Journal" pipeline:
// Budgets are compared against actuals in Reports rather than posting
// anything themselves, and Tax/Withholding rates are consumed *by* the
// Sales/Purchases line-item forms rather than posting independently.
const sideRelations = [
    {
        from: { label: 'Budgets', routeName: 'accounting.budgets.index', permission: 'accounts.view', icon: icons.chartPie },
        to: { label: 'Reports', routeName: 'reports.index', permission: 'reports.view', icon: icons.chartPie },
        caption: 'Budgeted targets are compared against actuals in the Budget vs Actual report',
    },
    {
        from: { label: 'Tax Rates', routeName: 'tax.rates.index', permission: 'tax.view', icon: icons.document },
        to: { label: 'Sales & Purchases', permission: null, icon: icons.cart },
        caption: 'Applied automatically on invoice and bill line items',
    },
    {
        from: { label: 'Leave & Overtime', routeName: 'hr.leaves.index', permission: 'leaves.manage', icon: icons.users },
        to: { label: 'Payroll', routeName: 'hr.payroll.index', permission: 'payroll.view', icon: icons.card },
        caption: 'Approved leave/overtime feeds the attendance-based salary deductions and additions',
    },
];

const visibleLanes = computed(() =>
    lanes
        .map((lane) => ({ ...lane, nodes: lane.nodes.filter((node) => can(node.permission)) }))
        .filter((lane) => lane.nodes.length > 0)
);

const visibleHub = computed(() => hub.filter((node) => can(node.permission)));

const visibleSideRelations = computed(() =>
    sideRelations.filter((relation) => can(relation.from.permission) && (relation.to.permission === null || can(relation.to.permission)))
);
</script>

<template>
    <div v-if="visibleLanes.length && visibleHub.length" class="space-y-4">
        <div class="overflow-x-auto">
            <div class="flex min-w-[900px] items-stretch gap-4">
                <!-- Lanes: every module that posts a transaction -->
                <div class="flex flex-1 flex-col justify-between gap-3">
                    <div v-for="lane in visibleLanes" :key="lane.label" class="flex items-center gap-2">
                        <span class="w-24 shrink-0 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ lane.label }}</span>
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

                <!-- Hub: every posted transaction lands here, in order -->
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

        <!-- Side relations: don't post through the Journal, but still connect two modules -->
        <div v-if="visibleSideRelations.length" class="flex flex-wrap gap-x-8 gap-y-3 border-t border-dashed border-gray-200 pt-4">
            <div v-for="relation in visibleSideRelations" :key="relation.caption" class="flex items-center gap-2 text-xs">
                <Link
                    :href="relation.from.routeName ? route(relation.from.routeName) : undefined"
                    class="flex shrink-0 items-center gap-2 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 font-medium text-gray-600 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700"
                >
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-for="(d, i) in relation.from.icon" :key="i" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="d" />
                    </svg>
                    {{ relation.from.label }}
                </Link>
                <svg class="h-4 w-4 shrink-0 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
                <Component
                    :is="relation.to.routeName ? Link : 'div'"
                    :href="relation.to.routeName ? route(relation.to.routeName) : undefined"
                    class="flex shrink-0 items-center gap-2 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 font-medium text-gray-600 transition"
                    :class="relation.to.routeName ? 'hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700' : ''"
                >
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-for="(d, i) in relation.to.icon" :key="i" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="d" />
                    </svg>
                    {{ relation.to.label }}
                </Component>
                <span class="text-gray-400">{{ relation.caption }}</span>
            </div>
        </div>
    </div>
</template>
