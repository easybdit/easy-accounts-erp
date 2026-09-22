<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import BillForm from './Partials/BillForm.vue';

const props = defineProps({
    bill: Object,
    vendors: Array,
    payableAccounts: Array,
    expenseAccounts: Array,
    products: Array,
    taxRates: Array,
});

const form = useForm({
    vendor_id: props.bill.vendor_id,
    payable_account_id: props.bill.payable_account_id,
    bill_date: props.bill.bill_date,
    due_date: props.bill.due_date,
    notes: props.bill.notes,
    items: props.bill.items.map((item) => ({
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
    form.put(route('purchases.bills.update', props.bill.id));
}
</script>

<template>
    <Head title="Edit Bill" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Purchases' },
            { label: 'Bills', href: route('purchases.bills.index') },
            { label: bill.bill_number },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit Bill — ${bill.bill_number}`" />
        </template>

        <form class="rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <BillForm
                :form="form"
                :vendors="vendors"
                :payable-accounts="payableAccounts"
                :expense-accounts="expenseAccounts"
                :products="products"
                :tax-rates="taxRates"
            />

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('purchases.bills.show', bill.id)">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
