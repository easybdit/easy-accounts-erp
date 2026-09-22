<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    open: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const navGroups = [
    {
        label: null,
        links: [{ label: 'Dashboard', routeName: 'dashboard' }],
    },
    {
        label: 'Accounting',
        links: [
            { label: 'Chart of Accounts', routeName: 'accounting.accounts.index' },
            { label: 'Journal', routeName: 'accounting.journals.index' },
        ],
    },
];

function isActive(routeName) {
    return route().current(routeName) || route().current(routeName + '.*');
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
            <div class="flex h-16 items-center gap-2 border-b border-gray-800 px-4">
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <ApplicationLogo class="h-8 w-auto fill-current text-white" />
                    <span class="text-lg font-semibold">EasyAccountsERP</span>
                </Link>
            </div>

            <nav class="flex-1 space-y-4 overflow-y-auto px-2 py-4">
                <div v-for="(group, index) in navGroups" :key="index">
                    <p
                        v-if="group.label"
                        class="px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-400"
                    >
                        {{ group.label }}
                    </p>
                    <Link
                        v-for="link in group.links"
                        :key="link.routeName"
                        :href="route(link.routeName)"
                        class="block rounded-md px-3 py-2 text-sm font-medium transition"
                        :class="isActive(link.routeName)
                            ? 'bg-gray-800 text-white'
                            : 'text-gray-300 hover:bg-gray-800 hover:text-white'"
                    >
                        {{ link.label }}
                    </Link>
                </div>
            </nav>
        </aside>
    </div>
</template>
