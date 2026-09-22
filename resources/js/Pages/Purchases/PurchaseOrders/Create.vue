<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import BillForm from '../Bills/Partials/BillForm.vue';

defineProps({
    vendors: Array,
    payableAccounts: Array,
    expenseAccounts: Array,
    taxRates: Array,
});

const form = useForm({
    vendor_id: '',
    payable_account_id: '',
    order_date: new Date().toISOString().slice(0, 10),
    expected_date: '',
    notes: '',
    items: [{ account_id: '', tax_rate_id: '', description: '', quantity: 1, unit_price: '', discount: 0 }],
});

function submit() {
    form.post(route('purchases.purchase-orders.store'));
}
</script>

<template>
    <Head title="New Purchase Order" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Purchases' },
            { label: 'Purchase Orders', href: route('purchases.purchase-orders.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Purchase Order" />
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

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('purchases.purchase-orders.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Purchase Order</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
