<script setup>
import { reactive, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    purchaseOrder: Object,
});

const confirmingConvert = ref(false);
const confirmingDelete = ref(false);

const statusVariant = {
    draft: 'neutral',
    sent: 'info',
    received: 'success',
    converted: 'success',
};

const billableItems = props.purchaseOrder.items.filter((item) => parseFloat(item.remaining_quantity) > 0);

// Defaults every line to its full remaining quantity, so submitting
// without changing anything reproduces the original one-click "convert
// everything" behavior exactly.
const quantities = reactive(
    Object.fromEntries(billableItems.map((item) => [item.id, item.remaining_quantity]))
);

const convertForm = useForm({});

function convert() {
    convertForm.transform(() => ({ quantities })).post(route('purchases.purchase-orders.convert', props.purchaseOrder.id), {
        onFinish: () => (confirmingConvert.value = false),
    });
}

function destroy() {
    router.delete(route('purchases.purchase-orders.destroy', props.purchaseOrder.id));
}
</script>

<template>
    <Head :title="purchaseOrder.po_number" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Purchases' },
            { label: 'Purchase Orders', href: route('purchases.purchase-orders.index') },
            { label: purchaseOrder.po_number },
        ]"
    >
        <template #header>
            <PageHeader :title="purchaseOrder.po_number">
                <template #actions>
                    <template v-if="purchaseOrder.is_editable">
                        <Link :href="route('purchases.purchase-orders.edit', purchaseOrder.id)">
                            <SecondaryButton type="button">Edit</SecondaryButton>
                        </Link>
                        <DangerButton type="button" @click="confirmingDelete = true">Delete</DangerButton>
                    </template>
                    <PrimaryButton v-if="!purchaseOrder.is_fully_billed" type="button" @click="confirmingConvert = true">
                        Convert to Bill
                    </PrimaryButton>
                </template>
            </PageHeader>
        </template>

        <div v-if="purchaseOrder.bills?.length" class="mb-4 rounded-md bg-indigo-50 px-4 py-3 text-sm text-indigo-700">
            This purchase order has been {{ purchaseOrder.is_fully_billed ? 'fully' : 'partially' }} converted to
            <template v-for="(bill, index) in purchaseOrder.bills" :key="bill.id">
                <Link :href="route('purchases.bills.show', bill.id)" class="font-medium underline">{{ bill.bill_number }}</Link
                >{{ index < purchaseOrder.bills.length - 1 ? ', ' : '' }}
            </template>
            .
        </div>

        <Card padded>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Vendor</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('vendors.show', purchaseOrder.vendor.id)" class="text-indigo-600 hover:text-indigo-900">
                            {{ purchaseOrder.vendor.name }}
                        </Link>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Order Date</dt>
                    <dd class="text-sm text-gray-800">{{ purchaseOrder.order_date }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Expected Date</dt>
                    <dd class="text-sm text-gray-800">{{ purchaseOrder.expected_date ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Status</dt>
                    <dd><Badge :variant="statusVariant[purchaseOrder.status]" class="capitalize">{{ purchaseOrder.status }}</Badge></dd>
                </div>
                <div v-if="purchaseOrder.notes" class="sm:col-span-4">
                    <dt class="text-xs font-medium uppercase text-gray-400">Notes</dt>
                    <dd class="text-sm text-gray-800">{{ purchaseOrder.notes }}</dd>
                </div>
            </dl>

            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Account</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Qty</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Billed</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Remaining</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Unit Price</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Discount</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tax</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Line Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in purchaseOrder.items" :key="item.id">
                            <td class="px-2 py-2 text-sm text-gray-700">{{ item.account.code }} — {{ item.account.name }}</td>
                            <td class="px-2 py-2 text-sm text-gray-500">{{ item.description }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ item.quantity }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ item.billed_quantity }}</td>
                            <td class="px-2 py-2 text-right text-sm" :class="parseFloat(item.remaining_quantity) > 0 ? 'font-medium text-amber-600' : 'text-gray-400'">
                                {{ item.remaining_quantity }}
                            </td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ item.unit_price }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ item.discount }}</td>
                            <td class="px-2 py-2 text-sm text-gray-500">
                                {{ item.tax_rate ? `${item.tax_rate.name} (${item.tax_amount})` : '—' }}
                            </td>
                            <td class="px-2 py-2 text-right text-sm font-medium text-gray-800">{{ item.line_total }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-end">
                <dl class="w-64 space-y-1 text-sm">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ purchaseOrder.subtotal }}</dd></div>
                    <div class="flex justify-between"><dt>Discount</dt><dd>{{ purchaseOrder.discount_total }}</dd></div>
                    <div class="flex justify-between"><dt>Tax</dt><dd>{{ purchaseOrder.tax_total }}</dd></div>
                    <div class="flex justify-between text-base font-semibold"><dt>Total</dt><dd>{{ purchaseOrder.total }}</dd></div>
                </dl>
            </div>
        </Card>

        <Modal :show="confirmingConvert" @close="confirmingConvert = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Convert to a bill</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Creates a new draft bill dated today. Adjust the quantities below to bill only part of what's
                    still remaining — e.g. if only some of the order has arrived — or leave them as-is to bill
                    everything remaining in one go.
                </p>

                <table class="mt-4 min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-2 py-1 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
                            <th class="px-2 py-1 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Remaining</th>
                            <th class="px-2 py-1 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Bill Now</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in billableItems" :key="item.id">
                            <td class="px-2 py-1 text-sm text-gray-700">{{ item.description }}</td>
                            <td class="px-2 py-1 text-right text-sm text-gray-500">{{ item.remaining_quantity }}</td>
                            <td class="px-2 py-1 text-right">
                                <input
                                    v-model="quantities[item.id]"
                                    type="number"
                                    step="0.0001"
                                    min="0"
                                    :max="item.remaining_quantity"
                                    class="w-28 rounded-md border-gray-300 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <InputError :message="convertForm.errors.quantities" class="mt-2" />

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingConvert = false">Cancel</SecondaryButton>
                    <PrimaryButton @click="convert">Convert</PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="confirmingDelete" @close="confirmingDelete = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Delete this purchase order?</h2>
                <p class="mt-1 text-sm text-gray-500">This action cannot be undone.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingDelete = false">Cancel</SecondaryButton>
                    <DangerButton @click="destroy">Delete</DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
