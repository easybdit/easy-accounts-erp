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

const props = defineProps({
    template: Object,
    customers: Array,
    receivableAccounts: Array,
    incomeAccounts: Array,
    taxRates: Array,
});

const form = useForm({
    name: props.template.name,
    customer_id: props.template.customer_id,
    receivable_account_id: props.template.receivable_account_id,
    notes: props.template.notes ?? '',
    is_active: props.template.is_active,
    items: props.template.items.map((item) => ({
        account_id: item.account_id,
        tax_rate_id: item.tax_rate_id ?? '',
        description: item.description,
        quantity: item.quantity,
        unit_price: item.unit_price,
        discount: item.discount,
    })),
});

function submit() {
    form.put(route('sales.recurring-invoices.update', props.template.id));
}
</script>

<template>
    <Head title="Edit Recurring Invoice" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Sales' },
            { label: 'Recurring Invoices', href: route('sales.recurring-invoices.index') },
            { label: template.name },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit — ${template.name}`" />
        </template>

        <form class="rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="mb-6">
                <InputLabel for="name" value="Template Name" />
                <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full max-w-md" required />
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
                <PrimaryButton :loading="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
