<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CompanyLogo from '@/Components/CompanyLogo.vue';
import { icons } from '@/icons';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    // Desktop icon-only rail (AdminLTE-style "sidebar-mini"). Has no effect
    // below the md breakpoint, where the sidebar is always full-width.
    collapsed: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const page = usePage();
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

function can(permission) {
    // A group/link with no permission key is available to every signed-in
    // user (e.g. the Help menu) rather than being hidden by default.
    if (!permission) return true;
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
            { label: 'Budgets', routeName: 'accounting.budgets.index' },
            { label: 'Company Settings', routeName: 'accounting.settings.edit', permission: 'settings.view' },
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
            { label: 'Sales Receipts', routeName: 'sales.sales-receipts.index' },
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
            { label: 'Recurring Bills', routeName: 'purchases.recurring-bills.index' },
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
            { label: 'Withholding Rates (TDS/VDS)', routeName: 'tax.withholding-rates.index' },
            { label: 'Tax Report', routeName: 'tax.report' },
        ],
    },
    {
        label: 'HR',
        icon: icons.users,
        permission: 'payroll.view',
        links: [
            { label: 'Payroll', routeName: 'hr.payroll.index' },
            { label: 'Payroll Components', routeName: 'hr.payroll-components.index' },
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
            { label: 'Login History', routeName: 'security.login-history.index', permission: 'audit.view' },
        ],
    },
    {
        label: 'Help',
        icon: icons.book,
        // No permission key: visible to every signed-in user, regardless of role.
        links: [
            { label: 'User Manual (বাংলা)', href: '/manuals/EasyAccountsERP_User_Manual_BN.docx' },
            { label: 'User Manual (English)', href: '/manuals/EasyAccountsERP_User_Manual_EN.docx' },
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
    // href-only links (e.g. Help's manual downloads) have no Inertia route
    // and are never "current".
    if (!routeName) return false;
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
    if (props.collapsed) return; // collapsed rail uses hover flyouts instead
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
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col overflow-visible bg-gray-900 text-gray-100 transition-all duration-200 ease-in-out md:static"
            :class="[open ? 'translate-x-0' : '-translate-x-full', 'md:translate-x-0', collapsed ? 'md:w-16' : 'md:w-64']"
        >
            <div class="flex h-16 shrink-0 items-center gap-2 border-b border-gray-800 px-4" :class="collapsed ? 'md:justify-center md:px-0' : ''">
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <CompanyLogo class="h-8 w-auto shrink-0 fill-current text-white" />
                    <span class="text-lg font-semibold" :class="collapsed ? 'md:hidden' : ''">{{ appName }}</span>
                </Link>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-visible px-2 py-4" :class="collapsed ? 'md:overflow-visible' : ''">
                <template v-for="group in navGroups" :key="group.label">
                    <!-- Flat item: single link, no submenu -->
                    <a
                        v-if="isFlat(group) && group.links[0].href"
                        :href="group.links[0].href"
                        download
                        :title="collapsed ? group.label : undefined"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-800 hover:text-white"
                        :class="collapsed ? 'md:justify-center md:px-2' : ''"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-for="(d, i) in group.icon" :key="i" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="d" />
                        </svg>
                        <span :class="collapsed ? 'md:hidden' : ''">{{ group.label }}</span>
                    </a>
                    <Link
                        v-else-if="isFlat(group)"
                        :href="route(group.links[0].routeName)"
                        :title="collapsed ? group.label : undefined"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition"
                        :class="[
                            isActive(group.links[0].routeName) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                            collapsed ? 'md:justify-center md:px-2' : '',
                        ]"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-for="(d, i) in group.icon" :key="i" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="d" />
                        </svg>
                        <span :class="collapsed ? 'md:hidden' : ''">{{ group.label }}</span>
                    </Link>

                    <!-- Treeview parent: toggles a nested submenu (inline accordion when
                         expanded, hover flyout when the rail is collapsed to icons) -->
                    <div v-else class="group relative">
                        <button
                            type="button"
                            :title="collapsed ? group.label : undefined"
                            class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm font-medium transition"
                            :class="[
                                groupIsActive(group) ? 'text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                collapsed ? 'md:justify-center md:px-2' : '',
                            ]"
                            @click="toggle(group.label)"
                        >
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-for="(d, i) in group.icon" :key="i" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="d" />
                            </svg>
                            <span class="flex-1" :class="collapsed ? 'md:hidden' : ''">{{ group.label }}</span>
                            <svg
                                class="h-4 w-4 shrink-0 transition-transform duration-200"
                                :class="[isOpen(group.label) ? 'rotate-90' : '', collapsed ? 'md:hidden' : '']"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>

                        <!-- Inline accordion (mobile always; desktop when not collapsed) -->
                        <div
                            class="grid transition-[grid-template-rows] duration-200 ease-in-out"
                            :class="[isOpen(group.label) ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]', collapsed ? 'md:hidden' : '']"
                        >
                            <ul class="overflow-hidden">
                                <li v-for="link in group.links" :key="link.routeName || link.href">
                                    <a
                                        v-if="link.href"
                                        :href="link.href"
                                        download
                                        class="my-0.5 ml-5 flex items-center gap-2 rounded-md py-2 pl-4 pr-3 text-sm text-gray-400 transition hover:bg-gray-800 hover:text-white"
                                    >
                                        <span class="h-1 w-1 shrink-0 rounded-full bg-current" />
                                        {{ link.label }}
                                    </a>
                                    <Link
                                        v-else
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

                        <!-- Hover flyout (desktop, collapsed rail only) -->
                        <div
                            v-if="collapsed"
                            class="pointer-events-none absolute left-full top-0 z-50 ml-1 hidden w-56 rounded-md bg-gray-800 py-2 opacity-0 shadow-xl ring-1 ring-black/20 transition-opacity duration-100 md:block md:group-hover:pointer-events-auto md:group-hover:opacity-100"
                        >
                            <p class="px-4 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ group.label }}</p>
                            <template v-for="link in group.links" :key="'fly-' + (link.routeName || link.href)">
                                <a
                                    v-if="link.href"
                                    :href="link.href"
                                    download
                                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-300 transition hover:bg-gray-700 hover:text-white"
                                >
                                    {{ link.label }}
                                </a>
                                <Link
                                    v-else
                                    :href="route(link.routeName)"
                                    class="flex items-center gap-2 px-4 py-2 text-sm transition"
                                    :class="isActive(link.routeName) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'"
                                >
                                    {{ link.label }}
                                </Link>
                            </template>
                        </div>
                    </div>
                </template>
            </nav>
        </aside>
    </div>
</template>
