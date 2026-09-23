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
    products: {
        type: Array,
        default: () => [],
    },
    liabilityAccounts: {
        type: Array,
        default: () => [],
    },
    taxRates: {
        type: Array,
        default: () => [],
    },
    showDates: {
        type: Boolean,
        default: true,
    },
    dateField: {
        type: String,
        default: 'invoice_date',
    },
    dateLabel: {
        type: String,
        default: 'Invoice Date',
    },
    dueDateField: {
        type: String,
        default: 'due_date',
    },
    dueDateLabel: {
        type: String,
        default: 'Due Date',
    },
    showTaxInclusive: {
        type: Boolean,
        default: false,
    },
    showDeferredRevenue: {
        type: Boolean,
        default: false,
    },
    showSecondTax: {
        type: Boolean,
        default: false,
    },
});

function addItem() {
    props.form.items.push({
        product_id: '',
        account_id: '',
        tax_rate_id: '',
        tax_rate_2_id: '',
        description: '',
        quantity: 1,
        unit_price: '',
        discount: 0,
        is_deferred: false,
        deferred_months: '',
        deferred_revenue_account_id: '',
    });
}

function removeItem(index) {
    if (props.form.items.length > 1) {
        props.form.items.splice(index, 1);
    }
}

// Selecting a product auto-fills the line from its catalog defaults; the
// user can still freely edit any field afterward (Section 32 integration).
function onProductChange(item, rawValue) {
    const productId = rawValue ? Number(rawValue) : '';
    item.product_id = productId;

    if (!productId) {
        return;
    }

    const product = props.products.find((p) => p.id === productId);
    if (!product) {
        return;
    }

    item.description = product.name;
    item.unit_price = product.selling_price;
    if (product.income_account_id) {
        item.account_id = product.income_account_id;
    }
}

function grossAfterDiscount(item) {
    const quantity = parseFloat(item.quantity) || 0;
    const unitPrice = parseFloat(item.unit_price) || 0;
    const discount = parseFloat(item.discount) || 0;

    return quantity * unitPrice - discount;
}

function findRate(id) {
    return props.taxRates.find((r) => r.id === id);
}

// Net (pre-tax) amount posted to the item's account. In exclusive mode this
// is just the entered gross; in inclusive mode the entered gross already
// contains tax, so the net is backed out of it using the COMBINED rate of
// both taxes (mirrors TaxRate::extractNet, generalized for two independent,
// non-compounding rates — collapses to the single-tax formula when only one
// is set).
function lineNet(item) {
    const gross = grossAfterDiscount(item);
    const rate = findRate(item.tax_rate_id);
    const rate2 = findRate(item.tax_rate_2_id);

    if (props.form.tax_inclusive && (rate || rate2)) {
        const combinedRate = (rate ? parseFloat(rate.rate) : 0) + (rate2 ? parseFloat(rate2.rate) : 0);
        return gross * (100 / (100 + combinedRate));
    }

    return gross;
}

// Each tax is calculated independently on the same net line total — not
// compounded on top of the other (Section 33).
function lineTax(item) {
    const net = lineNet(item);
    const rate = findRate(item.tax_rate_id);
    const rate2 = findRate(item.tax_rate_2_id);
    const tax1 = rate ? (net * parseFloat(rate.rate)) / 100 : 0;
    const tax2 = rate2 ? (net * parseFloat(rate2.rate)) / 100 : 0;

    return tax1 + tax2;
}

const subtotal = computed(() =>
    props.form.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0), 0)
);
const discountTotal = computed(() =>
    props.form.items.reduce((sum, item) => sum + (parseFloat(item.discount) || 0), 0)
);
const taxTotal = computed(() => props.form.items.reduce((sum, item) => sum + lineTax(item), 0));
const netTotal = computed(() => props.form.items.reduce((sum, item) => sum + lineNet(item), 0));
// netTotal + taxTotal, not subtotal - discountTotal + taxTotal: the two
// coincide in exclusive mode but only this form stays correct in inclusive
// mode, where each line's net already excludes tax (mirrors the backend).
const total = computed(() => netTotal.value + taxTotal.value);
</script>

<template>
    <div class="grid grid-cols-1 gap-6" :class="showDates ? 'sm:grid-cols-3' : 'sm:grid-cols-2'">
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

        <div v-if="showDates" class="grid grid-cols-2 gap-4">
            <div>
                <InputLabel :for="dateField" :value="dateLabel" />
                <TextInput :id="dateField" v-model="form[dateField]" type="date" class="mt-1 block w-full" required />
                <InputError :message="form.errors[dateField]" class="mt-2" />
            </div>
            <div>
                <InputLabel :for="dueDateField" :value="dueDateLabel" />
                <TextInput :id="dueDateField" v-model="form[dueDateField]" type="date" class="mt-1 block w-full" />
                <InputError :message="form.errors[dueDateField]" class="mt-2" />
            </div>
        </div>
    </div>

    <label v-if="showTaxInclusive" class="mt-4 flex items-center gap-2 text-sm text-gray-700">
        <input
            v-model="form.tax_inclusive"
            type="checkbox"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
        />
        Prices include tax
    </label>

    <div class="mt-6 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th v-if="products.length > 0" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Product</th>
                    <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Income Account</th>
                    <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
                    <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Qty</th>
                    <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Unit Price</th>
                    <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Discount</th>
                    <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tax</th>
                    <th v-if="showSecondTax" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tax 2</th>
                    <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Line Total</th>
                    <th class="px-2 py-2" />
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template v-for="(item, index) in form.items" :key="index">
                <tr>
                    <td v-if="products.length > 0" class="px-2 py-2">
                        <select
                            :value="item.product_id"
                            class="block w-40 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @change="onProductChange(item, $event.target.value)"
                        >
                            <option value="">Custom line</option>
                            <option v-for="product in products" :key="product.id" :value="product.id">
                                {{ product.sku }} — {{ product.name }}
                            </option>
                        </select>
                    </td>
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
                    <td v-if="showSecondTax" class="px-2 py-2">
                        <select
                            v-model="item.tax_rate_2_id"
                            class="block w-36 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option :value="''">No second tax</option>
                            <option v-for="rate in taxRates" :key="rate.id" :value="rate.id">
                                {{ rate.name }} ({{ rate.rate }}%)
                            </option>
                        </select>
                        <InputError :message="form.errors[`items.${index}.tax_rate_2_id`]" class="mt-1" />
                    </td>
                    <td class="whitespace-nowrap px-2 py-2 text-right text-sm text-gray-700">
                        {{ (lineNet(item) + lineTax(item)).toFixed(4) }}
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
                <tr v-if="showDeferredRevenue">
                    <td :colspan="(products.length > 0 ? 9 : 8) + (showSecondTax ? 1 : 0)" class="bg-gray-50 px-2 py-2">
                        <label class="flex items-center gap-2 text-xs text-gray-600">
                            <input
                                v-model="item.is_deferred"
                                type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            Defer this revenue
                        </label>
                        <div v-if="item.is_deferred" class="mt-2 flex flex-wrap items-start gap-3">
                            <div>
                                <InputLabel :for="`deferred_months_${index}`" value="Recognize over (months)" class="text-xs" />
                                <input
                                    :id="`deferred_months_${index}`"
                                    v-model="item.deferred_months"
                                    type="number"
                                    step="1"
                                    min="1"
                                    class="mt-1 block w-32 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <InputError :message="form.errors[`items.${index}.deferred_months`]" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel :for="`deferred_account_${index}`" value="Deferred Revenue Account" class="text-xs" />
                                <select
                                    :id="`deferred_account_${index}`"
                                    v-model="item.deferred_revenue_account_id"
                                    class="mt-1 block w-56 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="" disabled>Select an account</option>
                                    <option v-for="account in liabilityAccounts" :key="account.id" :value="account.id">
                                        {{ account.code }} — {{ account.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors[`items.${index}.deferred_revenue_account_id`]" class="mt-1" />
                            </div>
                        </div>
                        <p v-else class="mt-1 text-xs text-gray-400">
                            The income account above is credited immediately when this invoice is posted.
                        </p>
                    </td>
                </tr>
                </template>
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
