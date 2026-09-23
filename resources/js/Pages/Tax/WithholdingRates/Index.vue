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
    <Head title="Withholding Tax Rates" />

    <AppLayout :breadcrumbs="[{ label: 'Tax' }, { label: 'Withholding Rates' }]">
        <template #header>
            <PageHeader title="Withholding Tax Rates (TDS/VDS)">
                <template #actions>
                    <Link :href="route('tax.withholding-rates.create')">
                        <PrimaryButton>New Withholding Rate</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <p class="mb-4 text-sm text-gray-500">
            TDS (Tax Deducted at Source) and VDS (VAT Deducted at Source) rates you can apply when paying a
            vendor — the withheld amount is kept back instead of paid out, and posted to the liability account
            below to be deposited to the tax authority later.
        </p>

        <Card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Rate</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Liability Account</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="rate in rates" :key="rate.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ rate.name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ rate.rate }}%</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ rate.liability_account.code }} — {{ rate.liability_account.name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="rate.is_active ? 'success' : 'neutral'">
                                    {{ rate.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <Link :href="route('tax.withholding-rates.edit', rate.id)" class="mr-3 text-indigo-600 hover:text-indigo-900">
                                    Edit
                                </Link>
                                <Link
                                    :href="route('tax.withholding-rates.destroy', rate.id)"
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
            </div>
            <EmptyState
                v-if="rates.length === 0"
                title="No withholding tax rates found"
                description="Create a TDS/VDS rate to withhold tax when paying a vendor."
            />
        </Card>
    </AppLayout>
</template>
