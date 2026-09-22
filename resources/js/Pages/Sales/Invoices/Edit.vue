<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InvoiceForm from './Partials/InvoiceForm.vue';

const props = defineProps({
    invoice: Object,
    customers: Array,
    receivableAccounts: Array,
    incomeAccounts: Array,
    products: Array,
    taxRates: Array,
});

const form = useForm({
    customer_id: props.invoice.customer_id,
    receivable_account_id: props.invoice.receivable_account_id,
    invoice_date: props.invoice.invoice_date,
    due_date: props.invoice.due_date,
    notes: props.invoice.notes,
    items: props.invoice.items.map((item) => ({
        product_id: item.product_id ?? '',
        account_id: item.account_id,
        tax_rate_id: item.tax_rate_id ?? '',
        description: item.description,
        quantity: item.quantity,
        unit_price: item.unit_price,
        discount: item.discount,
    })),
});

function submit() {
    form.put(route('sales.invoices.update', props.invoice.id));
}
</script>

<template>
    <Head title="Edit Invoice" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Sales' },
            { label: 'Invoices', href: route('sales.invoices.index') },
            { label: invoice.invoice_number },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit Invoice — ${invoice.invoice_number}`" />
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
                <Link :href="route('sales.invoices.show', invoice.id)">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
