<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { icons } from '@/icons';

defineProps({
    open: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const page = usePage();
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

function can(permission) {
    return page.props.auth.permissions?.includes(permission) ?? false;
}

const allNavGroups = [
    {
        label: 'Dashboard',
        icon: icons.home,
        permission: 'dashboard.view',
        links: [{ label: 'Dashboard', routeName: 'dashboard' }],
    },
    {
        label: 'Accounting',
        icon: icons.calculator,
        permission: 'accounts.view',
        links: [
            { label: 'Chart of Accounts', routeName: 'accounting.accounts.index' },
            { label: 'Journal', routeName: 'accounting.journals.index' },
            { label: 'General Ledger', routeName: 'accounting.ledger.index' },
            { label: 'Trial Balance', routeName: 'accounting.trial-balance.index' },
            { label: 'Fixed Assets', routeName: 'accounting.fixed-assets.index' },
            { label: 'Period Lock', routeName: 'accounting.settings.edit', permission: 'settings.view' },
        ],
    },
    {
        label: 'Sales',
        icon: icons.cart,
        permission: 'invoices.view',
        links: [
            { label: 'Customers', routeName: 'customers.index' },
            { label: 'Estimates', routeName: 'sales.estimates.index' },
            { label: 'Invoices', routeName: 'sales.invoices.index' },
            { label: 'Credit Notes', routeName: 'sales.credit-notes.index' },
            { label: 'Recurring Invoices', routeName: 'sales.recurring-invoices.index' },
            { label: 'Payments', routeName: 'sales.payments.index' },
            { label: 'Deferred Revenue', routeName: 'sales.revenue-recognition.index' },
        ],
    },
    {
        label: 'Purchases',
        icon: icons.bag,
        permission: 'bills.view',
        links: [
            { label: 'Vendors', routeName: 'vendors.index' },
            { label: 'Purchase Orders', routeName: 'purchases.purchase-orders.index' },
            { label: 'Bills', routeName: 'purchases.bills.index' },
            { label: 'Vendor Credits', routeName: 'purchases.vendor-credits.index' },
            { label: 'Vendor Payments', routeName: 'purchases.vendor-payments.index' },
        ],
    },
    {
        label: 'Expenses',
        icon: icons.card,
        permission: 'expenses.view',
        links: [
            { label: 'Expenses', routeName: 'expenses.entries.index' },
            { label: 'Recurring', routeName: 'expenses.recurring.index' },
            { label: 'Categories', routeName: 'expenses.categories.index' },
        ],
    },
    {
        label: 'Banking',
        icon: icons.bank,
        permission: 'banking.view',
        links: [
            { label: 'Bank Accounts', routeName: 'banking.accounts.index' },
            { label: 'Deposits', routeName: 'banking.deposits.index' },
            { label: 'Transfers', routeName: 'banking.transfers.index' },
        ],
    },
    {
        label: 'Inventory',
        icon: icons.cube,
        permission: 'inventory.view',
        links: [
            { label: 'Products', routeName: 'inventory.products.index' },
            { label: 'Categories', routeName: 'inventory.categories.index' },
            { label: 'Stock Movements', routeName: 'inventory.stock-movements.index' },
        ],
    },
    {
        label: 'Tax',
        icon: icons.document,
        permission: 'tax.view',
        links: [
            { label: 'Tax Rates', routeName: 'tax.rates.index' },
            { label: 'Tax Report', routeName: 'tax.report' },
        ],
    },
    {
        label: 'Reports',
        icon: icons.chartPie,
        permission: 'reports.view',
        links: [{ label: 'All Reports', routeName: 'reports.index' }],
    },
    {
        label: 'Security',
        icon: icons.shield,
        permission: 'users.view',
        links: [
            { label: 'Users', routeName: 'security.users.index', permission: 'users.view' },
            { label: 'Roles', routeName: 'security.roles.index', permission: 'roles.view' },
            { label: 'Audit Log', routeName: 'security.audit-log.index', permission: 'audit.view' },
        ],
    },
];

const navGroups = computed(() =>
    allNavGroups
        .filter((group) => can(group.permission))
        .map((group) => ({
            ...group,
            links: group.links.filter((link) => !link.permission || can(link.permission)),
        }))
        .filter((group) => group.links.length > 0)
);

function isActive(routeName) {
    return route().current(routeName) || route().current(routeName + '.*');
}

function groupIsActive(group) {
    return group.links.some((link) => isActive(link.routeName));
}

// Multi-level: a group with exactly one link renders as a flat item (no
// caret/submenu needed); two or more links render as a collapsible parent,
// mirroring AdminLTE's treeview (mixed flat + nested menu items).
function isFlat(group) {
    return group.links.length === 1;
}

// The Sidebar remounts on every Inertia navigation (AppLayout isn't a
// persistent layout), so seeding open state from the current URL here is
// enough to keep the active group expanded on every page load.
const openGroups = ref(new Set(navGroups.value.filter((g) => !isFlat(g) && groupIsActive(g)).map((g) => g.label)));

function toggle(label) {
    if (openGroups.value.has(label)) {
        openGroups.value.delete(label);
    } else {
        openGroups.value.add(label);
    }
    // Reassign so Vue's reactivity picks up the mutated Set.
    openGroups.value = new Set(openGroups.value);
}

function isOpen(label) {
    return openGroups.value.has(label);
}
</script>

<template>
    <div>
        <!-- Mobile overlay -->
        <div
            v-if="open"
            class="fixed inset-0 z-30 bg-black/50 md:hidden"
            @click="emit('close')"
        />

        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-gray-900 text-gray-100 transition-transform duration-200 ease-in-out md:static md:translate-x-0"
            :class="open ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 shrink-0 items-center gap-2 border-b border-gray-800 px-4">
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <ApplicationLogo class="h-8 w-auto fill-current text-white" />
                    <span class="text-lg font-semibold">{{ appName }}</span>
                </Link>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-2 py-4">
                <template v-for="group in navGroups" :key="group.label">
                    <!-- Flat item: single link, no submenu -->
                    <Link
                        v-if="isFlat(group)"
                        :href="route(group.links[0].routeName)"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition"
                        :class="isActive(group.links[0].routeName)
                            ? 'bg-indigo-600 text-white'
                            : 'text-gray-300 hover:bg-gray-800 hover:text-white'"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-for="(d, i) in group.icon" :key="i" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="d" />
                        </svg>
                        {{ group.label }}
                    </Link>

                    <!-- Treeview parent: toggles a nested submenu -->
                    <div v-else>
                        <button
                            type="button"
                            class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm font-medium transition"
                            :class="groupIsActive(group) ? 'text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'"
                            @click="toggle(group.label)"
                        >
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-for="(d, i) in group.icon" :key="i" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="d" />
                            </svg>
                            <span class="flex-1">{{ group.label }}</span>
                            <svg
                                class="h-4 w-4 shrink-0 transition-transform duration-200"
                                :class="isOpen(group.label) ? 'rotate-90' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>

                        <div
                            class="grid transition-[grid-template-rows] duration-200 ease-in-out"
                            :class="isOpen(group.label) ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
                        >
                            <ul class="overflow-hidden">
                                <li v-for="link in group.links" :key="link.routeName">
                                    <Link
                                        :href="route(link.routeName)"
                                        class="my-0.5 ml-5 flex items-center gap-2 rounded-md py-2 pl-4 pr-3 text-sm transition"
                                        :class="isActive(link.routeName)
                                            ? 'bg-indigo-600 text-white'
                                            : 'text-gray-400 hover:bg-gray-800 hover:text-white'"
                                    >
                                        <span class="h-1 w-1 shrink-0 rounded-full bg-current" />
                                        {{ link.label }}
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </div>
                </template>
            </nav>
        </aside>
    </div>
</template>
