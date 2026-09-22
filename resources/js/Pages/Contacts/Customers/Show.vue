<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    customer: Object,
    openingBalance: String,
    currentBalance: String,
    entries: Array,
});
</script>

<template>
    <Head :title="customer.name" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Sales' },
            { label: 'Customers', href: route('customers.index') },
            { label: customer.name },
        ]"
    >
        <template #header>
            <PageHeader :title="customer.name">
                <template #actions>
                    <Link :href="route('customers.edit', customer.id)">
                        <SecondaryButton type="button">Edit</SecondaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-400">Email</p>
                <p class="text-sm text-gray-800">{{ customer.email ?? '—' }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-400">Phone</p>
                <p class="text-sm text-gray-800">{{ customer.phone ?? '—' }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-400">Opening Balance</p>
                <p class="text-sm text-gray-800">{{ openingBalance }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-400">Current Balance (Accounts Receivable)</p>
                <p class="text-lg font-semibold text-gray-900">{{ currentBalance }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <div class="border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Transaction History / Statement</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Account</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reference</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Debit</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Credit</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="entry in entries" :key="entry.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ entry.date }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ entry.account }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                            <Link :href="route('accounting.journals.show', entry.journal_id)" class="text-indigo-600 hover:text-indigo-900">
                                {{ entry.reference ?? `#${entry.journal_id}` }}
                            </Link>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ entry.description ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ entry.debit }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ entry.credit }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-800">{{ entry.running_balance }}</td>
                    </tr>
                    <tr v-if="entries.length === 0">
                        <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                            No transactions yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
