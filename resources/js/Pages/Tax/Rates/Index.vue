<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    rates: Array,
});

const page = usePage();
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

        <div
            v-if="page.props.flash?.success"
            class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700"
        >
            {{ page.props.flash.success }}
        </div>
        <div
            v-if="$page.props.errors?.rate"
            class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            {{ $page.props.errors.rate }}
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
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
                    <tr v-for="rate in rates" :key="rate.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ rate.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ rate.rate }}%</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ rate.tax_account.code }} — {{ rate.tax_account.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium"
                                :class="rate.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                            >
                                {{ rate.is_active ? 'Active' : 'Inactive' }}
                            </span>
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
                    <tr v-if="rates.length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                            No tax rates found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
