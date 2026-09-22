<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AccountForm from './Partials/AccountForm.vue';

const props = defineProps({
    accounts: Array,
    types: Array,
});

const form = useForm({
    code: '',
    name: '',
    type: '',
    parent_id: null,
    opening_balance: 0,
    is_active: true,
    is_bank_account: false,
});

function submit() {
    form.post(route('accounting.accounts.store'));
}
</script>

<template>
    <Head title="New Account" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Accounting' },
            { label: 'Chart of Accounts', href: route('accounting.accounts.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Account" />
        </template>

        <form class="max-w-3xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <AccountForm :form="form" :accounts="accounts" :types="types" />

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('accounting.accounts.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">Save Account</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
