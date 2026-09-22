<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    rates: Array,
});
</script>

<template>
    <Head title="Tax Rates" />

    <AppLayout :breadcrumbs="[{ label: 'Tax' }, { label: 'Rates' }]">
        <template #header>
            <PageHeader title="Tax Rates">
                <template #actions>
                    <Link :href="route('tax.rates.create')">
                        <PrimaryButton>New Tax Rate</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <Card>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Rate</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tax Account</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="rate in rates" :key="rate.id" class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ rate.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ rate.rate }}%</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ rate.tax_account.code }} — {{ rate.tax_account.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <Badge :variant="rate.is_active ? 'success' : 'neutral'">
                                {{ rate.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <Link :href="route('tax.rates.edit', rate.id)" class="mr-3 text-indigo-600 hover:text-indigo-900">
                                Edit
                            </Link>
                            <Link
                                :href="route('tax.rates.destroy', rate.id)"
                                method="delete"
                                as="button"
                                class="text-red-600 hover:text-red-900"
                            >
                                Delete
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-if="rates.length === 0" title="No tax rates found" description="Create a tax rate to start charging tax on invoices and bills." />
        </Card>
    </AppLayout>
</template>
