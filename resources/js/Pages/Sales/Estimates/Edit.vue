<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InvoiceForm from '../Invoices/Partials/InvoiceForm.vue';

const props = defineProps({
    estimate: Object,
    customers: Array,
    receivableAccounts: Array,
    incomeAccounts: Array,
    taxRates: Array,
});

const form = useForm({
    customer_id: props.estimate.customer_id,
    receivable_account_id: props.estimate.receivable_account_id,
    estimate_date: props.estimate.estimate_date,
    expiry_date: props.estimate.expiry_date ?? '',
    status: props.estimate.status,
    notes: props.estimate.notes ?? '',
    items: props.estimate.items.map((item) => ({
        account_id: item.account_id,
        tax_rate_id: item.tax_rate_id ?? '',
        description: item.description,
        quantity: item.quantity,
        unit_price: item.unit_price,
        discount: item.discount,
    })),
});

function submit() {
    form.put(route('sales.estimates.update', props.estimate.id));
}
</script>

<template>
    <Head title="Edit Estimate" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Sales' },
            { label: 'Estimates', href: route('sales.estimates.index') },
            { label: estimate.estimate_number },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit — ${estimate.estimate_number}`" />
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

            <div class="mt-4 max-w-xs">
                <InputLabel for="status" value="Status" />
                <select
                    id="status"
                    v-model="form.status"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="accepted">Accepted</option>
                    <option value="declined">Declined</option>
                </select>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('sales.estimates.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
