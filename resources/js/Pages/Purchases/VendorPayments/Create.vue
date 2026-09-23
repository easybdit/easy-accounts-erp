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
    vendors: Array,
    paymentAccounts: Array,
    openBills: Array,
    withholdingTaxRates: Array,
});

const form = useForm({
    vendor_id: '',
    payment_account_id: '',
    payment_date: new Date().toISOString().slice(0, 10),
    reference: '',
    method: '',
    amount: '',
    withholding_tax_rate_id: '',
    notes: '',
    allocations: [],
});

const withholdingPreview = computed(() => {
    const rate = props.withholdingTaxRates.find((r) => r.id === form.withholding_tax_rate_id);
    if (!rate) {
        return null;
    }
    const gross = parseFloat(form.amount) || 0;
    const withheld = (gross * parseFloat(rate.rate)) / 100;

    return { withheld, net: gross - withheld };
});

const selected = reactive({});

const vendorBills = computed(() =>
    props.openBills.filter((bill) => bill.vendor_id === form.vendor_id)
);

const allocatedTotal = computed(() =>
    Object.values(selected).reduce((sum, value) => sum + (parseFloat(value) || 0), 0)
);

const remaining = computed(() => (parseFloat(form.amount) || 0) - allocatedTotal.value);

function toggle(bill) {
    if (bill.id in selected) {
        delete selected[bill.id];
    } else {
        const remainingBeforeThis = (parseFloat(form.amount) || 0) - allocatedTotal.value;
        const dueAmount = parseFloat(bill.amount_due);
        selected[bill.id] = Math.max(0, Math.min(remainingBeforeThis, dueAmount)).toFixed(4);
    }
}

function submit() {
    form.transform((data) => ({
        ...data,
        allocations: Object.entries(selected).map(([bill_id, amount]) => ({ bill_id, amount })),
    })).post(route('purchases.vendor-payments.store'));
}
</script>

<template>
    <Head title="Make Payment" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Purchases' },
            { label: 'Vendor Payments', href: route('purchases.vendor-payments.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="Make Payment" />
        </template>

        <form class="rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div>
                    <InputLabel for="vendor_id" value="Vendor" />
                    <select
                        id="vendor_id"
                        v-model="form.vendor_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="" disabled>Select a vendor</option>
                        <option v-for="vendor in vendors" :key="vendor.id" :value="vendor.id">
                            {{ vendor.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.vendor_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="payment_account_id" value="Pay From" />
                    <select
                        id="payment_account_id"
                        v-model="form.payment_account_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="" disabled>Select an account</option>
                        <option v-for="account in paymentAccounts" :key="account.id" :value="account.id">
                            {{ account.code }} — {{ account.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.payment_account_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="amount" value="Amount Settled" />
                    <TextInput id="amount" v-model="form.amount" type="number" step="0.0001" class="mt-1 block w-full" required />
                    <p class="mt-1 text-xs text-gray-400">The full amount clearing the bill(s) below — before any TDS/VDS withheld.</p>
                    <InputError :message="form.errors.amount" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="withholding_tax_rate_id" value="Withhold TDS/VDS (optional)" />
                    <select
                        id="withholding_tax_rate_id"
                        v-model="form.withholding_tax_rate_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">None</option>
                        <option v-for="rate in withholdingTaxRates" :key="rate.id" :value="rate.id">
                            {{ rate.name }} ({{ rate.rate }}%)
                        </option>
                    </select>
                    <p v-if="withholdingPreview" class="mt-1 text-xs text-gray-400">
                        Withholds {{ withholdingPreview.withheld.toFixed(4) }} — net cash paid: {{ withholdingPreview.net.toFixed(4) }}.
                    </p>
                    <InputError :message="form.errors.withholding_tax_rate_id" class="mt-2" />
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
                <h3 class="text-sm font-semibold text-gray-700">Apply to Bills</h3>

                <p v-if="!form.vendor_id" class="mt-2 text-sm text-gray-500">Select a vendor to see their open bills.</p>
                <p v-else-if="vendorBills.length === 0" class="mt-2 text-sm text-gray-500">
                    This vendor has no open (posted, unpaid) bills.
                </p>

                <table v-else class="mt-3 min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-2 py-2" />
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Bill #</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Amount Due</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Apply Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="bill in vendorBills" :key="bill.id">
                            <td class="px-2 py-2">
                                <input
                                    type="checkbox"
                                    :checked="bill.id in selected"
                                    class="rounded border-gray-300"
                                    @change="toggle(bill)"
                                />
                            </td>
                            <td class="px-2 py-2 text-sm text-gray-700">{{ bill.bill_number }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ bill.total }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ bill.amount_due }}</td>
                            <td class="px-2 py-2 text-right">
                                <input
                                    v-if="bill.id in selected"
                                    v-model="selected[bill.id]"
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
                <Link :href="route('purchases.vendor-payments.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Make Payment</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
