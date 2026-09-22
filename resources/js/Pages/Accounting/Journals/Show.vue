<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    journal: Object,
});
</script>

<template>
    <Head :title="`Journal #${journal.id}`" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Accounting' },
            { label: 'Journal', href: route('accounting.journals.index') },
            { label: `#${journal.id}` },
        ]"
    >
        <template #header>
            <PageHeader :title="`Journal #${journal.id}`" />
        </template>

        <div class="rounded-lg bg-white p-6 shadow-sm">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Date</dt>
                    <dd class="text-sm text-gray-800">{{ journal.date }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Reference</dt>
                    <dd class="text-sm text-gray-800">{{ journal.reference ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Posted At</dt>
                    <dd class="text-sm text-gray-800">{{ journal.posted_at }}</dd>
                </div>
                <div class="sm:col-span-3">
                    <dt class="text-xs font-medium uppercase text-gray-400">Description</dt>
                    <dd class="text-sm text-gray-800">{{ journal.description ?? '—' }}</dd>
                </div>
            </dl>

            <table class="mt-6 min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Account</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
                        <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Debit</th>
                        <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Credit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="entry in journal.entries" :key="entry.id">
                        <td class="px-2 py-2 text-sm text-gray-700">{{ entry.account.code }} — {{ entry.account.name }}</td>
                        <td class="px-2 py-2 text-sm text-gray-500">{{ entry.description ?? '—' }}</td>
                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ entry.debit }}</td>
                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ entry.credit }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-4 flex justify-end">
                <Link :href="route('accounting.journals.index')" class="text-sm text-indigo-600 hover:text-indigo-900">
                    Back to Journal
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
