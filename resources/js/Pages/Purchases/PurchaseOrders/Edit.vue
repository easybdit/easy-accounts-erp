<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import BillForm from '../Bills/Partials/BillForm.vue';

const props = defineProps({
    purchaseOrder: Object,
    vendors: Array,
    payableAccounts: Array,
    expenseAccounts: Array,
    taxRates: Array,
});

const form = useForm({
    vendor_id: props.purchaseOrder.vendor_id,
    payable_account_id: props.purchaseOrder.payable_account_id,
    order_date: props.purchaseOrder.order_date,
    expected_date: props.purchaseOrder.expected_date ?? '',
    status: props.purchaseOrder.status,
    notes: props.purchaseOrder.notes ?? '',
    items: props.purchaseOrder.items.map((item) => ({
        account_id: item.account_id,
        tax_rate_id: item.tax_rate_id ?? '',
        description: item.description,
        quantity: item.quantity,
        unit_price: item.unit_price,
        discount: item.discount,
    })),
});

function submit() {
    form.put(route('purchases.purchase-orders.update', props.purchaseOrder.id));
}
</script>

<template>
    <Head title="Edit Purchase Order" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Purchases' },
            { label: 'Purchase Orders', href: route('purchases.purchase-orders.index') },
            { label: purchaseOrder.po_number },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit — ${purchaseOrder.po_number}`" />
        </template>

        <form class="rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <BillForm
                :form="form"
                :vendors="vendors"
                :payable-accounts="payableAccounts"
                :expense-accounts="expenseAccounts"
                :tax-rates="taxRates"
                date-field="order_date"
                date-label="Order Date"
                due-date-field="expected_date"
                due-date-label="Expected Date"
            />

            <div class="mt-4 max-w-xs">
                <InputLabel for="status" value="Status" />
                <select
                    id="status"
                    v-model="form.status"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="received">Received</option>
                </select>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('purchases.purchase-orders.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
