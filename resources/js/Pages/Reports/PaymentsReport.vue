<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    received: Array,
    made: Array,
    totalReceived: String,
    totalMade: String,
    from: String,
    to: String,
});

const from = ref(props.from ?? '');
const to = ref(props.to ?? '');

function apply() {
    router.get(
        route('reports.payments'),
        { from: from.value || undefined, to: to.value || undefined },
        { preserveState: true, replace: true }
    );
}
</script>

<template>
    <Head title="Payments Report" />

    <AppLayout :breadcrumbs="[{ label: 'Reports', href: route('reports.index') }, { label: 'Payments Report' }]">
        <template #header>
            <PageHeader title="Payments Report" />
        </template>

        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
            <div>
                <label class="block text-xs font-medium text-gray-500">From</label>
                <input v-model="from" type="date" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="apply" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">To</label>
                <input v-model="to" type="date" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="apply" />
            </div>
        </div>

        <div class="mb-6 overflow-hidden rounded-lg bg-white shadow-sm">
            <div class="border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Payments Received</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Payment #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="payment in received" :key="payment.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                            <Link :href="route('sales.payments.show', payment.id)" class="text-indigo-600 hover:text-indigo-900">{{ payment.payment_number }}</Link>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ payment.customer.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ payment.payment_date }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ payment.amount }}</td>
                    </tr>
                    <tr v-if="received.length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No payments received in this period.</td>
                    </tr>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr class="font-semibold">
                        <td class="px-4 py-3 text-right text-sm text-gray-700" colspan="3">Total</td>
                        <td class="px-4 py-3 text-right text-sm text-gray-900">{{ totalReceived }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <div class="border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Payments Made</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Payment #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Vendor</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="payment in made" :key="payment.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                            <Link :href="route('purchases.vendor-payments.show', payment.id)" class="text-indigo-600 hover:text-indigo-900">{{ payment.payment_number }}</Link>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ payment.vendor.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ payment.payment_date }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ payment.amount }}</td>
                    </tr>
                    <tr v-if="made.length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No payments made in this period.</td>
                    </tr>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr class="font-semibold">
                        <td class="px-4 py-3 text-right text-sm text-gray-700" colspan="3">Total</td>
                        <td class="px-4 py-3 text-right text-sm text-gray-900">{{ totalMade }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </AppLayout>
</template>
