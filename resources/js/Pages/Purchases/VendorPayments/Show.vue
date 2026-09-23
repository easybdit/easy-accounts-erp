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
            { label: 'Purchases' },
            { label: 'Vendor Payments', href: route('purchases.vendor-payments.index') },
            { label: payment.payment_number },
        ]"
    >
        <template #header>
            <PageHeader :title="payment.payment_number" />
        </template>

        <div class="rounded-lg bg-white p-6 shadow-sm">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Vendor</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('vendors.show', payment.vendor.id)" class="text-indigo-600 hover:text-indigo-900">
                            {{ payment.vendor.name }}
                        </Link>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Date</dt>
                    <dd class="text-sm text-gray-800">{{ payment.payment_date }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Paid From</dt>
                    <dd class="text-sm text-gray-800">{{ payment.payment_account.code }} — {{ payment.payment_account.name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Amount Settled</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ payment.amount }}</dd>
                </div>
                <div v-if="payment.withholding_tax_rate">
                    <dt class="text-xs font-medium uppercase text-gray-400">Withheld ({{ payment.withholding_tax_rate.name }})</dt>
                    <dd class="text-sm text-gray-800">{{ payment.withholding_tax_amount }}</dd>
                </div>
                <div v-if="payment.withholding_tax_rate">
                    <dt class="text-xs font-medium uppercase text-gray-400">Net Cash Paid</dt>
                    <dd class="text-sm font-semibold text-gray-900">{{ payment.net_cash_paid }}</dd>
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
                <div v-if="payment.notes" class="sm:col-span-4">
                    <dt class="text-xs font-medium uppercase text-gray-400">Notes</dt>
                    <dd class="text-sm text-gray-800">{{ payment.notes }}</dd>
                </div>
            </dl>

            <table class="mt-6 min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Bill</th>
                        <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Bill Total</th>
                        <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Applied Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="allocation in payment.allocations" :key="allocation.id">
                        <td class="px-2 py-2 text-sm text-gray-700">
                            <Link :href="route('purchases.bills.show', allocation.bill.id)" class="text-indigo-600 hover:text-indigo-900">
                                {{ allocation.bill.bill_number }}
                            </Link>
                        </td>
                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ allocation.bill.total }}</td>
                        <td class="px-2 py-2 text-right text-sm font-medium text-gray-800">{{ allocation.amount }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
