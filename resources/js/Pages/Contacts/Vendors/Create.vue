<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PartyForm from '@/Components/PartyForm.vue';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    address: '',
    billing_address: '',
    opening_balance: 0,
    is_active: true,
});

function submit() {
    form.post(route('vendors.store'));
}
</script>

<template>
    <Head title="New Vendor" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Purchases' },
            { label: 'Vendors', href: route('vendors.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Vendor" />
        </template>

        <form class="max-w-3xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <PartyForm :form="form" />

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('vendors.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Vendor</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
