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
    assets: Object,
    filters: Object,
});

const status = ref(props.filters.status ?? '');

watch(status, (value) => {
    router.get(
        route('accounting.fixed-assets.index'),
        { status: value || undefined },
        { preserveState: true, replace: true }
    );
});

const statusVariant = {
    active: 'success',
    fully_depreciated: 'info',
    disposed: 'neutral',
};
</script>

<template>
    <Head title="Fixed Assets" />

    <AppLayout :breadcrumbs="[{ label: 'Accounting' }, { label: 'Fixed Assets' }]">
        <template #header>
            <PageHeader title="Fixed Assets">
                <template #actions>
                    <Link :href="route('accounting.fixed-assets.create')">
                        <PrimaryButton>New Asset</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="mb-4">
            <select
                v-model="status"
                class="block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="fully_depreciated">Fully Depreciated</option>
                <option value="disposed">Disposed</option>
            </select>
        </div>

        <Card>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Purchase Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Cost</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Accumulated Dep.</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Book Value</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="asset in assets.data" :key="asset.id" class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                            <Link :href="route('accounting.fixed-assets.show', asset.id)" class="text-indigo-600 hover:text-indigo-900">
                                {{ asset.name }}
                            </Link>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ asset.purchase_date }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ asset.purchase_cost }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ asset.accumulated_depreciation }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-800">{{ asset.book_value }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <Badge :variant="statusVariant[asset.status]" class="capitalize">{{ asset.status.replace('_', ' ') }}</Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-if="assets.data.length === 0" title="No fixed assets found" description="Add a server or equipment purchase to start tracking depreciation." />
        </Card>

        <div v-if="assets.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, index) in assets.links"
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
