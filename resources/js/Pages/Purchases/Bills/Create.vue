<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import BillForm from './Partials/BillForm.vue';

const props = defineProps({
    vendors: Array,
    payableAccounts: Array,
    expenseAccounts: Array,
    taxRates: Array,
});

const form = useForm({
    vendor_id: '',
    payable_account_id: '',
    bill_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    notes: '',
    items: [{ account_id: '', tax_rate_id: '', description: '', quantity: 1, unit_price: '', discount: 0 }],
});

function submit() {
    form.post(route('purchases.bills.store'));
}
</script>

<template>
    <Head title="New Bill" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Purchases' },
            { label: 'Bills', href: route('purchases.bills.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Bill" />
        </template>

        <form class="rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <BillForm
                :form="form"
                :vendors="vendors"
                :payable-accounts="payableAccounts"
                :expense-accounts="expenseAccounts"
                :tax-rates="taxRates"
            />

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('purchases.bills.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Draft</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
