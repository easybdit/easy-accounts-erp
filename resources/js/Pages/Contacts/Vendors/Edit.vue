<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PartyForm from '@/Components/PartyForm.vue';

const props = defineProps({
    vendor: Object,
});

const form = useForm({
    name: props.vendor.name,
    email: props.vendor.email,
    phone: props.vendor.phone,
    address: props.vendor.address,
    billing_address: props.vendor.billing_address,
    opening_balance: props.vendor.opening_balance,
    is_active: props.vendor.is_active,
});

function submit() {
    form.put(route('vendors.update', props.vendor.id));
}
</script>

<template>
    <Head title="Edit Vendor" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Purchases' },
            { label: 'Vendors', href: route('vendors.index') },
            { label: vendor.name },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit Vendor — ${vendor.name}`" />
        </template>

        <form class="max-w-3xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <PartyForm :form="form" />

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('vendors.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
