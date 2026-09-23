<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Card from '@/Components/Card.vue';

const props = defineProps({
    receipt: Object,
});
</script>

<template>
    <Head :title="receipt.receipt_number" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Sales' },
            { label: 'Sales Receipts', href: route('sales.sales-receipts.index') },
            { label: receipt.receipt_number },
        ]"
    >
        <template #header>
            <PageHeader :title="receipt.receipt_number">
                <template #actions>
                    <a :href="route('sales.sales-receipts.pdf', receipt.id)">
                        <SecondaryButton type="button">Download PDF</SecondaryButton>
                    </a>
                </template>
            </PageHeader>
        </template>

        <Card padded>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Customer</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('customers.show', receipt.customer.id)" class="text-indigo-600 hover:text-indigo-900">
                            {{ receipt.customer.name }}
                        </Link>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Receipt Date</dt>
                    <dd class="text-sm text-gray-800">{{ receipt.receipt_date }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Deposited To</dt>
                    <dd class="text-sm text-gray-800">{{ receipt.deposit_account.code }} — {{ receipt.deposit_account.name }}</dd>
                </div>
                <div v-if="receipt.journal">
                    <dt class="text-xs font-medium uppercase text-gray-400">Posted Journal</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('accounting.journals.show', receipt.journal.id)" class="text-indigo-600 hover:text-indigo-900">
                            #{{ receipt.journal.id }}
                        </Link>
                    </dd>
                </div>
                <div v-if="receipt.notes" class="sm:col-span-4">
                    <dt class="text-xs font-medium uppercase text-gray-400">Notes</dt>
                    <dd class="text-sm text-gray-800">{{ receipt.notes }}</dd>
                </div>
            </dl>

            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Account</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Qty</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Unit Price</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Discount</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tax</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Line Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in receipt.items" :key="item.id">
                            <td class="px-2 py-2 text-sm text-gray-700">{{ item.account.code }} — {{ item.account.name }}</td>
                            <td class="px-2 py-2 text-sm text-gray-500">{{ item.description }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ item.quantity }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ item.unit_price }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ item.discount }}</td>
                            <td class="px-2 py-2 text-sm text-gray-500">
                                <span v-if="item.tax_rate">{{ item.tax_rate.name }} ({{ item.tax_amount }})</span>
                                <span v-else>—</span>
                                <span v-if="item.tax_rate_2" class="block">{{ item.tax_rate_2.name }} ({{ item.tax_amount_2 }})</span>
                            </td>
                            <td class="px-2 py-2 text-right text-sm font-medium text-gray-800">{{ item.line_total }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-end">
                <dl class="w-64 space-y-1 text-sm">
                    <div class="flex justify-between">
                        <dt>Subtotal</dt>
                        <dd>{{ receipt.subtotal }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Discount</dt>
                        <dd>{{ receipt.discount_total }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Tax</dt>
                        <dd>{{ receipt.tax_total }}</dd>
                    </div>
                    <div class="flex justify-between text-base font-semibold">
                        <dt>Total</dt>
                        <dd>{{ receipt.total }}</dd>
                    </div>
                </dl>
            </div>
        </Card>
    </AppLayout>
</template>
