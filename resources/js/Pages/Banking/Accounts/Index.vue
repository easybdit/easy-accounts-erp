<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    accounts: Array,
});
</script>

<template>
    <Head title="Bank Accounts" />

    <AppLayout :breadcrumbs="[{ label: 'Banking' }, { label: 'Accounts' }]">
        <template #header>
            <PageHeader title="Bank Accounts">
                <template #actions>
                    <Link :href="route('banking.transfers.create')">
                        <PrimaryButton>New Transfer</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <Card>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Account</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Balance</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="account in accounts" :key="account.id" class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ account.code }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ account.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-800">{{ account.balance }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <Link
                                :href="route('accounting.ledger.index', { account_id: account.id })"
                                class="mr-3 text-indigo-600 hover:text-indigo-900"
                            >
                                View Transactions
                            </Link>
                            <Link :href="route('banking.reconciliation.index', account.id)" class="text-indigo-600 hover:text-indigo-900">
                                Reconcile
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-if="accounts.length === 0" title="No bank accounts yet">
                Mark an account as Bank/Cash on the
                <Link :href="route('accounting.accounts.index')" class="text-indigo-600 hover:text-indigo-900">Chart of Accounts</Link>
                page.
            </EmptyState>
        </Card>
    </AppLayout>
</template>
