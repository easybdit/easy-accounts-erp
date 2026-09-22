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
    name: '',
    customer_id: '',
    receivable_account_id: '',
    notes: '',
    is_active: true,
    items: [{ account_id: '', tax_rate_id: '', description: '', quantity: 1, unit_price: '', discount: 0 }],
});

function submit() {
    form.post(route('sales.recurring-invoices.store'));
}
</script>

<template>
    <Head title="New Recurring Invoice" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Sales' },
            { label: 'Recurring Invoices', href: route('sales.recurring-invoices.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Recurring Invoice Template" />
        </template>

        <form class="rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="mb-6">
                <InputLabel for="name" value="Template Name" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    placeholder="e.g. Monthly Retainer — Acme Corp"
                    class="mt-1 block w-full max-w-md"
                    required
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <InvoiceForm
                :form="form"
                :customers="customers"
                :receivable-accounts="receivableAccounts"
                :income-accounts="incomeAccounts"
                :tax-rates="taxRates"
                :show-dates="false"
            />

            <div class="mt-4 flex items-center gap-2">
                <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded border-gray-300" />
                <InputLabel for="is_active" value="Active" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('sales.recurring-invoices.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Template</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
