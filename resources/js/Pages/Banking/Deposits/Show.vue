<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';

defineProps({
    deposit: Object,
});
</script>

<template>
    <Head :title="deposit.deposit_number" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Banking' },
            { label: 'Deposits', href: route('banking.deposits.index') },
            { label: deposit.deposit_number },
        ]"
    >
        <template #header>
            <PageHeader :title="deposit.deposit_number" />
        </template>

        <Card padded>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Bank Account</dt>
                    <dd class="text-sm text-gray-800">{{ deposit.bank_account.code }} — {{ deposit.bank_account.name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Deposit Date</dt>
                    <dd class="text-sm text-gray-800">{{ deposit.deposit_date }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Amount</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ deposit.amount }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Reference</dt>
                    <dd class="text-sm text-gray-800">{{ deposit.reference ?? '—' }}</dd>
                </div>
                <div v-if="deposit.journal">
                    <dt class="text-xs font-medium uppercase text-gray-400">Posted Journal</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('accounting.journals.show', deposit.journal.id)" class="text-indigo-600 hover:text-indigo-900">
                            #{{ deposit.journal.id }}
                        </Link>
                    </dd>
                </div>
                <div v-if="deposit.notes" class="sm:col-span-4">
                    <dt class="text-xs font-medium uppercase text-gray-400">Notes</dt>
                    <dd class="text-sm text-gray-800">{{ deposit.notes }}</dd>
                </div>
            </dl>

            <h3 class="mt-6 text-xs font-medium uppercase text-gray-400">Payments in this Deposit</h3>
            <table class="mt-2 min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Payment #</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Customer</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="payment in deposit.payments" :key="payment.id">
                        <td class="px-2 py-2 text-sm text-gray-700">
                            <Link :href="route('sales.payments.show', payment.id)" class="text-indigo-600 hover:text-indigo-900">
                                {{ payment.payment_number }}
                            </Link>
                        </td>
                        <td class="px-2 py-2 text-sm text-gray-700">{{ payment.customer.name }}</td>
                        <td class="px-2 py-2 text-sm text-gray-500">{{ payment.payment_date }}</td>
                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ payment.amount }}</td>
                    </tr>
                </tbody>
            </table>
        </Card>
    </AppLayout>
</template>
