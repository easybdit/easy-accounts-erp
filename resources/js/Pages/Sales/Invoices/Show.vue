<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
    invoice: Object,
    amountPaid: String,
    amountDue: String,
});

const confirmingPost = ref(false);
const confirmingDelete = ref(false);

function post() {
    router.post(route('sales.invoices.post', props.invoice.id), {}, {
        onFinish: () => (confirmingPost.value = false),
    });
}

function destroy() {
    router.delete(route('sales.invoices.destroy', props.invoice.id));
}
</script>

<template>
    <Head :title="invoice.invoice_number" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Sales' },
            { label: 'Invoices', href: route('sales.invoices.index') },
            { label: invoice.invoice_number },
        ]"
    >
        <template #header>
            <PageHeader :title="invoice.invoice_number">
                <template #actions>
                    <template v-if="invoice.status === 'draft'">
                        <Link :href="route('sales.invoices.edit', invoice.id)">
                            <SecondaryButton type="button">Edit</SecondaryButton>
                        </Link>
                        <DangerButton type="button" @click="confirmingDelete = true">Delete</DangerButton>
                        <PrimaryButton type="button" @click="confirmingPost = true">Post Invoice</PrimaryButton>
                    </template>
                    <Link v-else-if="parseFloat(amountDue) > 0" :href="route('sales.payments.create')">
                        <PrimaryButton type="button">Receive Payment</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <Card padded>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Customer</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('customers.show', invoice.customer.id)" class="text-indigo-600 hover:text-indigo-900">
                            {{ invoice.customer.name }}
                        </Link>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Invoice Date</dt>
                    <dd class="text-sm text-gray-800">{{ invoice.invoice_date }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Due Date</dt>
                    <dd class="text-sm text-gray-800">{{ invoice.due_date ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Status</dt>
                    <dd>
                        <Badge :variant="invoice.status === 'posted' ? 'success' : 'warning'" class="capitalize">
                            {{ invoice.status }}
                        </Badge>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Receivable Account</dt>
                    <dd class="text-sm text-gray-800">{{ invoice.receivable_account.code }} — {{ invoice.receivable_account.name }}</dd>
                </div>
                <div v-if="invoice.status === 'posted'">
                    <dt class="text-xs font-medium uppercase text-gray-400">Paid / Due</dt>
                    <dd class="text-sm text-gray-800">
                        {{ amountPaid }} paid —
                        <span :class="parseFloat(amountDue) > 0 ? 'font-semibold text-red-600' : 'font-semibold text-green-600'">
                            {{ amountDue }} due
                        </span>
                    </dd>
                </div>
                <div v-if="invoice.journal">
                    <dt class="text-xs font-medium uppercase text-gray-400">Posted Journal</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('accounting.journals.show', invoice.journal.id)" class="text-indigo-600 hover:text-indigo-900">
                            #{{ invoice.journal.id }}
                        </Link>
                    </dd>
                </div>
                <div v-if="invoice.notes" class="sm:col-span-4">
                    <dt class="text-xs font-medium uppercase text-gray-400">Notes</dt>
                    <dd class="text-sm text-gray-800">{{ invoice.notes }}</dd>
                </div>
            </dl>

            <table class="mt-6 min-w-full divide-y divide-gray-200">
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
                    <tr v-for="item in invoice.items" :key="item.id">
                        <td class="px-2 py-2 text-sm text-gray-700">{{ item.account.code }} — {{ item.account.name }}</td>
                        <td class="px-2 py-2 text-sm text-gray-500">{{ item.description }}</td>
                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ item.quantity }}</td>
                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ item.unit_price }}</td>
                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ item.discount }}</td>
                        <td class="px-2 py-2 text-sm text-gray-500">
                            {{ item.tax_rate ? `${item.tax_rate.name} (${item.tax_amount})` : '—' }}
                        </td>
                        <td class="px-2 py-2 text-right text-sm font-medium text-gray-800">{{ item.line_total }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-4 flex justify-end">
                <dl class="w-64 space-y-1 text-sm">
                    <div class="flex justify-between">
                        <dt>Subtotal</dt>
                        <dd>{{ invoice.subtotal }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Discount</dt>
                        <dd>{{ invoice.discount_total }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Tax</dt>
                        <dd>{{ invoice.tax_total }}</dd>
                    </div>
                    <div class="flex justify-between text-base font-semibold">
                        <dt>Total</dt>
                        <dd>{{ invoice.total }}</dd>
                    </div>
                </dl>
            </div>

            <div v-if="invoice.payment_allocations?.length" class="mt-6 border-t border-gray-100 pt-4">
                <h2 class="mb-2 text-sm font-semibold text-gray-700">Payment History</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Payment #</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Applied Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="allocation in invoice.payment_allocations" :key="allocation.id">
                            <td class="px-2 py-2 text-sm text-gray-700">
                                <Link :href="route('sales.payments.show', allocation.payment.id)" class="text-indigo-600 hover:text-indigo-900">
                                    {{ allocation.payment.payment_number }}
                                </Link>
                            </td>
                            <td class="px-2 py-2 text-sm text-gray-500">{{ allocation.payment.payment_date }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ allocation.amount }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <Modal :show="confirmingPost" @close="confirmingPost = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Post this invoice?</h2>
                <p class="mt-1 text-sm text-gray-500">
                    This creates the accounting journal entry and locks the invoice from further edits.
                    This cannot be undone from here.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingPost = false">Cancel</SecondaryButton>
                    <PrimaryButton @click="post">Post</PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="confirmingDelete" @close="confirmingDelete = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Delete this draft invoice?</h2>
                <p class="mt-1 text-sm text-gray-500">This action cannot be undone.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingDelete = false">Cancel</SecondaryButton>
                    <DangerButton @click="destroy">Delete</DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
