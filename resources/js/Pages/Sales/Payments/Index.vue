<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    payments: Object,
    filters: Object,
});

const page = usePage();
const search = ref(props.filters.search ?? '');

watch(search, (value) => {
    router.get(
        route('sales.payments.index'),
        { search: value || undefined },
        { preserveState: true, replace: true }
    );
});
</script>

<template>
    <Head title="Payments" />

    <AppLayout :breadcrumbs="[{ label: 'Sales' }, { label: 'Payments' }]">
        <template #header>
            <PageHeader title="Payments">
                <template #actions>
                    <Link :href="route('sales.payments.create')">
                        <PrimaryButton>Receive Payment</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div
            v-if="page.props.flash?.success"
            class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700"
        >
            {{ page.props.flash.success }}
        </div>

        <div class="mb-4">
            <input
                v-model="search"
                type="text"
                placeholder="Search by payment #, reference, or customer..."
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs"
            />
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Payment #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Method</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="payment in payments.data" :key="payment.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                            <Link :href="route('sales.payments.show', payment.id)" class="text-indigo-600 hover:text-indigo-900">
                                {{ payment.payment_number }}
                            </Link>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ payment.customer.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ payment.payment_date }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ payment.method ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ payment.amount }}</td>
                    </tr>
                    <tr v-if="payments.data.length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                            No payments found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="payments.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, index) in payments.links"
                :key="index"
                :href="link.url ?? '#'"
                v-html="link.label"
                class="rounded-md px-3 py-1 text-sm"
                :class="[
                    link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                    !link.url ? 'pointer-events-none opacity-50' : '',
                ]"
            />
        </div>
    </AppLayout>
</template>
