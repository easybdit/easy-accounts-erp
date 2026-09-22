<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import BillForm from '../Bills/Partials/BillForm.vue';

const props = defineProps({
    vendorCredit: Object,
    vendors: Array,
    payableAccounts: Array,
    expenseAccounts: Array,
    taxRates: Array,
});

const form = useForm({
    vendor_id: props.vendorCredit.vendor_id,
    payable_account_id: props.vendorCredit.payable_account_id,
    bill_id: props.vendorCredit.bill_id ?? '',
    vendor_credit_date: props.vendorCredit.vendor_credit_date,
    notes: props.vendorCredit.notes ?? '',
    items: props.vendorCredit.items.map((item) => ({
        account_id: item.account_id,
        tax_rate_id: item.tax_rate_id ?? '',
        description: item.description,
        quantity: item.quantity,
        unit_price: item.unit_price,
        discount: item.discount,
    })),
});

function submit() {
    form.put(route('purchases.vendor-credits.update', props.vendorCredit.id));
}
</script>

<template>
    <Head title="Edit Vendor Credit" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Purchases' },
            { label: 'Vendor Credits', href: route('purchases.vendor-credits.index') },
            { label: vendorCredit.vendor_credit_number },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit — ${vendorCredit.vendor_credit_number}`" />
        </template>

        <form class="rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel for="vendor_credit_date" value="Vendor Credit Date" />
                    <TextInput id="vendor_credit_date" v-model="form.vendor_credit_date" type="date" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.vendor_credit_date" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="bill_id" value="Related Bill (optional)" />
                    <TextInput id="bill_id" v-model="form.bill_id" type="number" placeholder="Bill ID, if any" class="mt-1 block w-full" />
                    <InputError :message="form.errors.bill_id" class="mt-2" />
                </div>
            </div>

            <BillForm
                :form="form"
                :vendors="vendors"
                :payable-accounts="payableAccounts"
                :expense-accounts="expenseAccounts"
                :tax-rates="taxRates"
                :show-dates="false"
            />

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('purchases.vendor-credits.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
