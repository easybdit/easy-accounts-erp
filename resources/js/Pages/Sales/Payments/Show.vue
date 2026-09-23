<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    payment: Object,
});
</script>

<template>
    <Head :title="payment.payment_number" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Sales' },
            { label: 'Payments', href: route('sales.payments.index') },
            { label: payment.payment_number },
        ]"
    >
        <template #header>
            <PageHeader :title="payment.payment_number" />
        </template>

        <div class="rounded-lg bg-white p-6 shadow-sm">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Customer</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('customers.show', payment.customer.id)" class="text-indigo-600 hover:text-indigo-900">
                            {{ payment.customer.name }}
                        </Link>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Date</dt>
                    <dd class="text-sm text-gray-800">{{ payment.payment_date }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Deposited To</dt>
                    <dd class="text-sm text-gray-800">{{ payment.deposit_account.code }} — {{ payment.deposit_account.name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Amount</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ payment.amount }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Method</dt>
                    <dd class="text-sm text-gray-800">{{ payment.method ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Reference</dt>
                    <dd class="text-sm text-gray-800">{{ payment.reference ?? '—' }}</dd>
                </div>
                <div v-if="payment.journal">
                    <dt class="text-xs font-medium uppercase text-gray-400">Posted Journal</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('accounting.journals.show', payment.journal.id)" class="text-indigo-600 hover:text-indigo-900">
                            #{{ payment.journal.id }}
                        </Link>
                    </dd>
                </div>
                <div v-if="payment.deposit_account.is_undeposited_funds">
                    <dt class="text-xs font-medium uppercase text-gray-400">Deposit Status</dt>
                    <dd class="text-sm text-gray-800">
                        <Link
                            v-if="payment.bank_deposit"
                            :href="route('banking.deposits.show', payment.bank_deposit.id)"
                            class="text-indigo-600 hover:text-indigo-900"
                        >
                            Deposited — {{ payment.bank_deposit.deposit_number }}
                        </Link>
                        <span v-else class="text-amber-600">Awaiting deposit</span>
                    </dd>
                </div>
                <div v-if="payment.notes" class="sm:col-span-4">
                    <dt class="text-xs font-medium uppercase text-gray-400">Notes</dt>
                    <dd class="text-sm text-gray-800">{{ payment.notes }}</dd>
                </div>
            </dl>

            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Invoice</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Invoice Total</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Applied Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="allocation in payment.allocations" :key="allocation.id">
                            <td class="px-2 py-2 text-sm text-gray-700">
                                <Link :href="route('sales.invoices.show', allocation.invoice.id)" class="text-indigo-600 hover:text-indigo-900">
                                    {{ allocation.invoice.invoice_number }}
                                </Link>
                            </td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ allocation.invoice.total }}</td>
                            <td class="px-2 py-2 text-right text-sm font-medium text-gray-800">{{ allocation.amount }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
