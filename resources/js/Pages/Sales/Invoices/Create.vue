<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InvoiceForm from './Partials/InvoiceForm.vue';

const props = defineProps({
    customers: Array,
    receivableAccounts: Array,
    incomeAccounts: Array,
    products: Array,
    taxRates: Array,
});

const form = useForm({
    customer_id: '',
    receivable_account_id: '',
    invoice_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    notes: '',
    items: [{ product_id: '', account_id: '', tax_rate_id: '', description: '', quantity: 1, unit_price: '', discount: 0 }],
});

function submit() {
    form.post(route('sales.invoices.store'));
}
</script>

<template>
    <Head title="New Invoice" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Sales' },
            { label: 'Invoices', href: route('sales.invoices.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Invoice" />
        </template>

        <form class="rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <InvoiceForm
                :form="form"
                :customers="customers"
                :receivable-accounts="receivableAccounts"
                :income-accounts="incomeAccounts"
                :products="products"
                :tax-rates="taxRates"
            />

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('sales.invoices.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Draft</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
