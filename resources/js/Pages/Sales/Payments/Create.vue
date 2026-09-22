<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    customers: Array,
    depositAccounts: Array,
    openInvoices: Array,
});

const form = useForm({
    customer_id: '',
    deposit_account_id: '',
    payment_date: new Date().toISOString().slice(0, 10),
    reference: '',
    method: '',
    amount: '',
    notes: '',
    allocations: [],
});

// invoice_id -> allocated amount string, only for checked invoices
const selected = reactive({});

const customerInvoices = computed(() =>
    props.openInvoices.filter((invoice) => invoice.customer_id === form.customer_id)
);

const allocatedTotal = computed(() =>
    Object.values(selected).reduce((sum, value) => sum + (parseFloat(value) || 0), 0)
);

const remaining = computed(() => (parseFloat(form.amount) || 0) - allocatedTotal.value);

function toggle(invoice) {
    if (invoice.id in selected) {
        delete selected[invoice.id];
    } else {
        const remainingBeforeThis = (parseFloat(form.amount) || 0) - allocatedTotal.value;
        const dueAmount = parseFloat(invoice.amount_due);
        selected[invoice.id] = Math.max(0, Math.min(remainingBeforeThis, dueAmount)).toFixed(4);
    }
}

function submit() {
    form.transform((data) => ({
        ...data,
        allocations: Object.entries(selected).map(([invoice_id, amount]) => ({ invoice_id, amount })),
    })).post(route('sales.payments.store'));
}
</script>

<template>
    <Head title="Receive Payment" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Sales' },
            { label: 'Payments', href: route('sales.payments.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="Receive Payment" />
        </template>

        <form class="rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div>
                    <InputLabel for="customer_id" value="Customer" />
                    <select
                        id="customer_id"
                        v-model="form.customer_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="" disabled>Select a customer</option>
                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                            {{ customer.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.customer_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="deposit_account_id" value="Deposit To" />
                    <select
                        id="deposit_account_id"
                        v-model="form.deposit_account_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="" disabled>Select an account</option>
                        <option v-for="account in depositAccounts" :key="account.id" :value="account.id">
                            {{ account.code }} — {{ account.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.deposit_account_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="amount" value="Amount Received" />
                    <TextInput id="amount" v-model="form.amount" type="number" step="0.0001" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.amount" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="payment_date" value="Payment Date" />
                    <TextInput id="payment_date" v-model="form.payment_date" type="date" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.payment_date" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="method" value="Method (optional)" />
                    <TextInput id="method" v-model="form.method" type="text" placeholder="Cash, Bank Transfer, Cheque..." class="mt-1 block w-full" />
                    <InputError :message="form.errors.method" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="reference" value="Reference (optional)" />
                    <TextInput id="reference" v-model="form.reference" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.reference" class="mt-2" />
                </div>
            </div>

            <div class="mt-6">
                <h3 class="text-sm font-semibold text-gray-700">Apply to Invoices</h3>

                <p v-if="!form.customer_id" class="mt-2 text-sm text-gray-500">Select a customer to see their open invoices.</p>
                <p v-else-if="customerInvoices.length === 0" class="mt-2 text-sm text-gray-500">
                    This customer has no open (posted, unpaid) invoices.
                </p>

                <table v-else class="mt-3 min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-2 py-2" />
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Invoice #</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Amount Due</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Apply Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="invoice in customerInvoices" :key="invoice.id">
                            <td class="px-2 py-2">
                                <input
                                    type="checkbox"
                                    :checked="invoice.id in selected"
                                    class="rounded border-gray-300"
                                    @change="toggle(invoice)"
                                />
                            </td>
                            <td class="px-2 py-2 text-sm text-gray-700">{{ invoice.invoice_number }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ invoice.total }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ invoice.amount_due }}</td>
                            <td class="px-2 py-2 text-right">
                                <input
                                    v-if="invoice.id in selected"
                                    v-model="selected[invoice.id]"
                                    type="number"
                                    step="0.0001"
                                    class="block w-32 rounded-md border-gray-300 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>

                <InputError :message="form.errors.allocations" class="mt-2" />
                <p v-if="form.amount" class="mt-2 text-sm" :class="remaining === 0 ? 'text-green-600' : 'text-red-600'">
                    Remaining unallocated: {{ remaining.toFixed(4) }}
                </p>
            </div>

            <div class="mt-6">
                <InputLabel for="notes" value="Notes (optional)" />
                <textarea
                    id="notes"
                    v-model="form.notes"
                    rows="2"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('sales.payments.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Receive Payment</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
