<script setup>
import { computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    customers: {
        type: Array,
        default: () => [],
    },
    receivableAccounts: {
        type: Array,
        default: () => [],
    },
    incomeAccounts: {
        type: Array,
        default: () => [],
    },
    taxRates: {
        type: Array,
        default: () => [],
    },
});

function addItem() {
    props.form.items.push({ account_id: '', tax_rate_id: '', description: '', quantity: 1, unit_price: '', discount: 0 });
}

function removeItem(index) {
    if (props.form.items.length > 1) {
        props.form.items.splice(index, 1);
    }
}

function lineTotal(item) {
    const quantity = parseFloat(item.quantity) || 0;
    const unitPrice = parseFloat(item.unit_price) || 0;
    const discount = parseFloat(item.discount) || 0;

    return quantity * unitPrice - discount;
}

function lineTax(item) {
    const rate = props.taxRates.find((r) => r.id === item.tax_rate_id);
    return rate ? (lineTotal(item) * parseFloat(rate.rate)) / 100 : 0;
}

const subtotal = computed(() =>
    props.form.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0), 0)
);
const discountTotal = computed(() =>
    props.form.items.reduce((sum, item) => sum + (parseFloat(item.discount) || 0), 0)
);
const taxTotal = computed(() => props.form.items.reduce((sum, item) => sum + lineTax(item), 0));
const total = computed(() => subtotal.value - discountTotal.value + taxTotal.value);
</script>

<template>
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
            <InputLabel for="receivable_account_id" value="Receivable Account" />
            <select
                id="receivable_account_id"
                v-model="form.receivable_account_id"
                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                <option value="" disabled>Select an account</option>
                <option v-for="account in receivableAccounts" :key="account.id" :value="account.id">
                    {{ account.code }} — {{ account.name }}
                </option>
            </select>
            <InputError :message="form.errors.receivable_account_id" class="mt-2" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <InputLabel for="invoice_date" value="Invoice Date" />
                <TextInput id="invoice_date" v-model="form.invoice_date" type="date" class="mt-1 block w-full" required />
                <InputError :message="form.errors.invoice_date" class="mt-2" />
            </div>
            <div>
                <InputLabel for="due_date" value="Due Date" />
                <TextInput id="due_date" v-model="form.due_date" type="date" class="mt-1 block w-full" />
                <InputError :message="form.errors.due_date" class="mt-2" />
            </div>
        </div>
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Income Account</th>
                    <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
                    <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Qty</th>
                    <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Unit Price</th>
                    <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Discount</th>
                    <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tax</th>
                    <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Line Total</th>
                    <th class="px-2 py-2" />
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="(item, index) in form.items" :key="index">
                    <td class="px-2 py-2">
                        <select
                            v-model="item.account_id"
                            class="block w-48 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="" disabled>Select account</option>
                            <option v-for="account in incomeAccounts" :key="account.id" :value="account.id">
                                {{ account.code }} — {{ account.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors[`items.${index}.account_id`]" class="mt-1" />
                    </td>
                    <td class="px-2 py-2">
                        <input
                            v-model="item.description"
                            type="text"
                            class="block w-48 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </td>
                    <td class="px-2 py-2">
                        <input
                            v-model="item.quantity"
                            type="number"
                            step="0.0001"
                            class="block w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </td>
                    <td class="px-2 py-2">
                        <input
                            v-model="item.unit_price"
                            type="number"
                            step="0.0001"
                            class="block w-32 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </td>
                    <td class="px-2 py-2">
                        <input
                            v-model="item.discount"
                            type="number"
                            step="0.0001"
                            class="block w-28 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </td>
                    <td class="px-2 py-2">
                        <select
                            v-model="item.tax_rate_id"
                            class="block w-36 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option :value="''">No tax</option>
                            <option v-for="rate in taxRates" :key="rate.id" :value="rate.id">
                                {{ rate.name }} ({{ rate.rate }}%)
                            </option>
                        </select>
                        <InputError :message="form.errors[`items.${index}.tax_rate_id`]" class="mt-1" />
                    </td>
                    <td class="whitespace-nowrap px-2 py-2 text-right text-sm text-gray-700">
                        {{ (lineTotal(item) + lineTax(item)).toFixed(4) }}
                    </td>
                    <td class="px-2 py-2 text-right">
                        <button
                            type="button"
                            class="text-sm text-red-600 hover:text-red-800"
                            :disabled="form.items.length <= 1"
                            @click="removeItem(index)"
                        >
                            Remove
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <InputError :message="form.errors.items" class="mt-2" />

        <button type="button" class="mt-3 text-sm font-medium text-indigo-600 hover:text-indigo-800" @click="addItem">
            + Add line
        </button>
    </div>

    <div class="mt-6 space-y-1 border-t border-gray-100 pt-4 text-right text-sm">
        <p>Subtotal: <strong>{{ subtotal.toFixed(4) }}</strong></p>
        <p>Discount: <strong>{{ discountTotal.toFixed(4) }}</strong></p>
        <p>Tax: <strong>{{ taxTotal.toFixed(4) }}</strong></p>
        <p class="text-base">Total: <strong>{{ total.toFixed(4) }}</strong></p>
    </div>
    <p class="mt-1 text-right text-xs text-gray-400">
        Shown for feedback only — the backend recalculates and is authoritative (Section 45).
    </p>

    <div class="mt-4">
        <InputLabel for="notes" value="Notes (optional)" />
        <textarea
            id="notes"
            v-model="form.notes"
            rows="2"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
    </div>
</template>
