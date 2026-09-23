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
    name: '',
    vendor_id: '',
    payable_account_id: '',
    notes: '',
    is_active: true,
    next_generation_date: '',
    items: [{ account_id: '', tax_rate_id: '', description: '', quantity: 1, unit_price: '', discount: 0 }],
});

function submit() {
    form.post(route('purchases.recurring-bills.store'));
}
</script>

<template>
    <Head title="New Recurring Bill" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Purchases' },
            { label: 'Recurring Bills', href: route('purchases.recurring-bills.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Recurring Bill Template" />
        </template>

        <form class="rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="mb-6">
                <InputLabel for="name" value="Template Name" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    placeholder="e.g. Monthly Datacenter Bandwidth"
                    class="mt-1 block w-full max-w-md"
                    required
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <BillForm
                :form="form"
                :vendors="vendors"
                :payable-accounts="payableAccounts"
                :expense-accounts="expenseAccounts"
                :tax-rates="taxRates"
                :show-dates="false"
            />

            <div class="mt-4 max-w-md">
                <InputLabel for="next_generation_date" value="Next Auto-Generation Date (optional)" />
                <TextInput id="next_generation_date" v-model="form.next_generation_date" type="date" class="mt-1 block w-full" />
                <p class="mt-1 text-xs text-gray-400">
                    If set, a draft bill is generated automatically on this date and then every month after —
                    still requires review before posting. Leave blank to keep this template manual-only ("Generate Now").
                </p>
                <InputError :message="form.errors.next_generation_date" class="mt-2" />
            </div>

            <div class="mt-4 flex items-center gap-2">
                <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded border-gray-300" />
                <InputLabel for="is_active" value="Active" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('purchases.recurring-bills.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Template</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
