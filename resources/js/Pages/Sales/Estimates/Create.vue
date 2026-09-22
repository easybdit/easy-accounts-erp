<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InvoiceForm from '../Invoices/Partials/InvoiceForm.vue';

defineProps({
    customers: Array,
    receivableAccounts: Array,
    incomeAccounts: Array,
    taxRates: Array,
});

const form = useForm({
    customer_id: '',
    receivable_account_id: '',
    estimate_date: new Date().toISOString().slice(0, 10),
    expiry_date: '',
    notes: '',
    items: [{ account_id: '', tax_rate_id: '', description: '', quantity: 1, unit_price: '', discount: 0 }],
});

function submit() {
    form.post(route('sales.estimates.store'));
}
</script>

<template>
    <Head title="New Estimate" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Sales' },
            { label: 'Estimates', href: route('sales.estimates.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Estimate" />
        </template>

        <form class="rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <InvoiceForm
                :form="form"
                :customers="customers"
                :receivable-accounts="receivableAccounts"
                :income-accounts="incomeAccounts"
                :tax-rates="taxRates"
                date-field="estimate_date"
                date-label="Estimate Date"
                due-date-field="expiry_date"
                due-date-label="Expiry Date"
            />

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('sales.estimates.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Estimate</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
