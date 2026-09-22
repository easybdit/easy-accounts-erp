<script setup>
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
    outcome: String, // 'success' | 'failed' | 'cancelled'
});

const messages = {
    success: {
        title: 'Payment Successful',
        body: 'Thank you — your payment has been received and applied to the invoice.',
        color: 'text-green-700',
    },
    failed: {
        title: 'Payment Failed',
        body: 'We could not confirm this payment. If money was deducted, please contact us and we will investigate.',
        color: 'text-red-700',
    },
    cancelled: {
        title: 'Payment Cancelled',
        body: 'You cancelled the payment. No money was charged. You can use the payment link again whenever you are ready.',
        color: 'text-gray-700',
    },
};

const message = messages[props.outcome] ?? messages.failed;
</script>

<template>
    <Head :title="message.title" />

    <GuestLayout>
        <div class="text-center">
            <h1 class="text-lg font-semibold" :class="message.color">{{ message.title }}</h1>
            <p class="mt-2 text-sm text-gray-600">{{ message.body }}</p>
        </div>
    </GuestLayout>
</template>
