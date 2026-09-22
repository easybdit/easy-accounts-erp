<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AccountForm from './Partials/AccountForm.vue';

const props = defineProps({
    account: Object,
    accounts: Array,
    types: Array,
});

const form = useForm({
    code: props.account.code,
    name: props.account.name,
    type: props.account.type,
    parent_id: props.account.parent_id,
    opening_balance: props.account.opening_balance,
    is_active: props.account.is_active,
    is_bank_account: props.account.is_bank_account,
    cash_flow_category: props.account.cash_flow_category,
});

function submit() {
    form.put(route('accounting.accounts.update', props.account.id));
}
</script>

<template>
    <Head title="Edit Account" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Accounting' },
            { label: 'Chart of Accounts', href: route('accounting.accounts.index') },
            { label: account.code },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit Account — ${account.code}`" />
        </template>

        <form class="max-w-3xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <AccountForm :form="form" :accounts="accounts" :types="types" />

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('accounting.accounts.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
