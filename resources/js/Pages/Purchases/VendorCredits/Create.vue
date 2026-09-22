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

defineProps({
    vendors: Array,
    payableAccounts: Array,
    expenseAccounts: Array,
    taxRates: Array,
});

const form = useForm({
    vendor_id: '',
    payable_account_id: '',
    bill_id: '',
    vendor_credit_date: new Date().toISOString().slice(0, 10),
    notes: '',
    items: [{ account_id: '', tax_rate_id: '', description: '', quantity: 1, unit_price: '', discount: 0 }],
});

function submit() {
    form.post(route('purchases.vendor-credits.store'));
}
</script>

<template>
    <Head title="New Vendor Credit" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Purchases' },
            { label: 'Vendor Credits', href: route('purchases.vendor-credits.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Vendor Credit" />
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
                <PrimaryButton :loading="form.processing">Save Draft</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
