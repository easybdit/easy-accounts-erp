<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    purchaseOrders: Object,
    filters: Object,
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

watch([search, status], () => {
    router.get(
        route('purchases.purchase-orders.index'),
        { search: search.value || undefined, status: status.value || undefined },
        { preserveState: true, replace: true }
    );
});

const statusVariant = {
    draft: 'neutral',
    sent: 'info',
    received: 'success',
    converted: 'success',
};
</script>

<template>
    <Head title="Purchase Orders" />

    <AppLayout :breadcrumbs="[{ label: 'Purchases' }, { label: 'Purchase Orders' }]">
        <template #header>
            <PageHeader title="Purchase Orders">
                <template #actions>
                    <Link :href="route('purchases.purchase-orders.create')">
                        <PrimaryButton>New Purchase Order</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="mb-4 flex flex-wrap gap-3">
            <div>
                <label for="search" class="sr-only">Search purchase orders</label>
                <input
                    id="search"
                    v-model="search"
                    type="text"
                    placeholder="Search by PO # or vendor..."
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs"
                />
            </div>
            <div>
                <label for="status" class="sr-only">Filter by status</label>
                <select
                    id="status"
                    v-model="status"
                    class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="received">Received</option>
                    <option value="converted">Converted</option>
                </select>
            </div>
        </div>

        <Card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">PO #</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Vendor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="po in purchaseOrders.data" :key="po.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                <Link :href="route('purchases.purchase-orders.show', po.id)" class="text-indigo-600 hover:text-indigo-900">
                                    {{ po.po_number }}
                                </Link>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ po.vendor.name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ po.order_date }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ po.total }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="statusVariant[po.status]" class="capitalize">{{ po.status }}</Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-if="purchaseOrders.data.length === 0" title="No purchase orders found" description="Create a purchase order before receiving goods from a vendor." />
        </Card>

        <div v-if="purchaseOrders.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, index) in purchaseOrders.links"
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
