<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PartyForm from '@/Components/PartyForm.vue';

const props = defineProps({
    customer: Object,
});

const form = useForm({
    name: props.customer.name,
    email: props.customer.email,
    phone: props.customer.phone,
    address: props.customer.address,
    billing_address: props.customer.billing_address,
    opening_balance: props.customer.opening_balance,
    is_active: props.customer.is_active,
});

function submit() {
    form.put(route('customers.update', props.customer.id));
}
</script>

<template>
    <Head title="Edit Customer" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Sales' },
            { label: 'Customers', href: route('customers.index') },
            { label: customer.name },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit Customer — ${customer.name}`" />
        </template>

        <form class="max-w-3xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <PartyForm :form="form" />

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('customers.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
