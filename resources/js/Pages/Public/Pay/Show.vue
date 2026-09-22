<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    token: String,
    invoiceNumber: String,
    customerName: String,
    amountDue: String,
    currency: String,
    alreadyPaid: Boolean,
});

const page = usePage();

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
</script>

<template>
    <Head :title="`Pay Invoice ${invoiceNumber}`" />

    <GuestLayout>
        <div class="text-center">
            <h1 class="text-lg font-semibold text-gray-900">Invoice {{ invoiceNumber }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ customerName }}</p>
        </div>

        <div v-if="page.props.flash?.error" class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ page.props.flash.error }}
        </div>

        <div v-if="alreadyPaid" class="mt-6 rounded-md bg-green-50 px-4 py-4 text-center text-sm text-green-700">
            This invoice has already been paid in full. Thank you!
        </div>

        <template v-else>
            <div class="mt-6 rounded-md bg-gray-50 px-4 py-4 text-center">
                <p class="text-xs uppercase tracking-wide text-gray-400">Amount Due</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ currency }} {{ amountDue }}</p>
            </div>

            <form method="POST" :action="route('pay.initiate', token)">
                <input type="hidden" name="_token" :value="csrfToken" />
                <PrimaryButton type="submit" class="mt-6 w-full justify-center">Pay Now</PrimaryButton>
            </form>

            <p class="mt-3 text-center text-xs text-gray-400">
                You'll be redirected to SSLCommerz to complete payment via card, mobile banking, or bank transfer.
            </p>
        </template>
    </GuestLayout>
</template>
