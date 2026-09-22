<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
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
    invoice_id: '',
    credit_note_date: new Date().toISOString().slice(0, 10),
    notes: '',
    items: [{ account_id: '', tax_rate_id: '', description: '', quantity: 1, unit_price: '', discount: 0 }],
});

function submit() {
    form.post(route('sales.credit-notes.store'));
}
</script>

<template>
    <Head title="New Credit Note" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Sales' },
            { label: 'Credit Notes', href: route('sales.credit-notes.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Credit Note" />
        </template>

        <form class="rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel for="credit_note_date" value="Credit Note Date" />
                    <TextInput id="credit_note_date" v-model="form.credit_note_date" type="date" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.credit_note_date" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="invoice_id" value="Related Invoice (optional)" />
                    <TextInput id="invoice_id" v-model="form.invoice_id" type="number" placeholder="Invoice ID, if any" class="mt-1 block w-full" />
                    <InputError :message="form.errors.invoice_id" class="mt-2" />
                </div>
            </div>

            <InvoiceForm
                :form="form"
                :customers="customers"
                :receivable-accounts="receivableAccounts"
                :income-accounts="incomeAccounts"
                :tax-rates="taxRates"
                :show-dates="false"
            />

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('sales.credit-notes.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Draft</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
