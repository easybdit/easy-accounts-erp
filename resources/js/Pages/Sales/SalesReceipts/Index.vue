<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    receipts: Object,
    filters: Object,
});

const search = ref(props.filters.search ?? '');

function apply() {
    router.get(
        route('sales.sales-receipts.index'),
        { search: search.value || undefined },
        { preserveState: true, replace: true }
    );
}

watch(search, apply);
</script>

<template>
    <Head title="Sales Receipts" />

    <AppLayout :breadcrumbs="[{ label: 'Sales' }, { label: 'Sales Receipts' }]">
        <template #header>
            <PageHeader title="Sales Receipts">
                <template #actions>
                    <Link :href="route('sales.sales-receipts.create')">
                        <PrimaryButton>New Sales Receipt</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="mb-4">
            <label for="search" class="sr-only">Search sales receipts</label>
            <input
                id="search"
                v-model="search"
                type="text"
                placeholder="Search by receipt # or customer..."
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs"
            />
        </div>

        <Card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Receipt #</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Customer</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="receipt in receipts.data" :key="receipt.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                <Link :href="route('sales.sales-receipts.show', receipt.id)" class="text-indigo-600 hover:text-indigo-900">
                                    {{ receipt.receipt_number }}
                                </Link>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ receipt.customer.name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ receipt.receipt_date }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ receipt.total }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-if="receipts.data.length === 0" title="No sales receipts found" description="Record a sales receipt for a cash sale." />
        </Card>

        <div v-if="receipts.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, index) in receipts.links"
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
