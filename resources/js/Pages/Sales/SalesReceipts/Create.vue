<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SalesReceiptForm from './Partials/SalesReceiptForm.vue';

const props = defineProps({
    customers: Array,
    depositAccounts: Array,
    incomeAccounts: Array,
    products: Array,
    taxRates: Array,
});

const form = useForm({
    customer_id: '',
    deposit_account_id: '',
    receipt_date: new Date().toISOString().slice(0, 10),
    tax_inclusive: false,
    notes: '',
    items: [{
        product_id: '', account_id: '', tax_rate_id: '', tax_rate_2_id: '', description: '', quantity: 1, unit_price: '', discount: 0,
    }],
});

function submit() {
    form.post(route('sales.sales-receipts.store'));
}
</script>

<template>
    <Head title="New Sales Receipt" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Sales' },
            { label: 'Sales Receipts', href: route('sales.sales-receipts.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Sales Receipt" />
        </template>

        <form class="rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <SalesReceiptForm
                :form="form"
                :customers="customers"
                :deposit-accounts="depositAccounts"
                :income-accounts="incomeAccounts"
                :products="products"
                :tax-rates="taxRates"
            />

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('sales.sales-receipts.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Record Sales Receipt</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
