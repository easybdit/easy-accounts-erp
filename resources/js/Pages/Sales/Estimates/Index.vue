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
    estimates: Object,
    filters: Object,
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

function apply() {
    router.get(
        route('sales.estimates.index'),
        { search: search.value || undefined, status: status.value || undefined },
        { preserveState: true, replace: true }
    );
}

watch([search, status], apply);

const statusVariant = {
    draft: 'neutral',
    sent: 'info',
    accepted: 'success',
    declined: 'danger',
    converted: 'success',
};
</script>

<template>
    <Head title="Estimates" />

    <AppLayout :breadcrumbs="[{ label: 'Sales' }, { label: 'Estimates' }]">
        <template #header>
            <PageHeader title="Estimates">
                <template #actions>
                    <Link :href="route('sales.estimates.create')">
                        <PrimaryButton>New Estimate</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="mb-4 flex flex-wrap gap-3">
            <input
                v-model="search"
                type="text"
                placeholder="Search by estimate # or customer..."
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs"
            />
            <select
                v-model="status"
                class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="sent">Sent</option>
                <option value="accepted">Accepted</option>
                <option value="declined">Declined</option>
                <option value="converted">Converted</option>
            </select>
        </div>

        <Card>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estimate #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="estimate in estimates.data" :key="estimate.id" class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                            <Link :href="route('sales.estimates.show', estimate.id)" class="text-indigo-600 hover:text-indigo-900">
                                {{ estimate.estimate_number }}
                            </Link>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ estimate.customer.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ estimate.estimate_date }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ estimate.total }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <Badge :variant="statusVariant[estimate.status]" class="capitalize">{{ estimate.status }}</Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-if="estimates.data.length === 0" title="No estimates found" description="Create an estimate to send a customer a quote before invoicing." />
        </Card>

        <div v-if="estimates.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, index) in estimates.links"
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
