<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    holidays: Array,
});
</script>

<template>
    <Head title="Holidays" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'Holidays' }]">
        <template #header>
            <PageHeader title="Holidays">
                <template #actions>
                    <Link :href="route('hr.holidays.create')">
                        <PrimaryButton>New Holiday</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <Card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Recurring</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="holiday in holidays" :key="holiday.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ holiday.name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ holiday.date }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="holiday.is_recurring_yearly ? 'info' : 'neutral'">
                                    {{ holiday.is_recurring_yearly ? 'Yearly' : 'One-time' }}
                                </Badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <Link :href="route('hr.holidays.edit', holiday.id)" class="mr-3 text-indigo-600 hover:text-indigo-900">
                                    Edit
                                </Link>
                                <Link
                                    :href="route('hr.holidays.destroy', holiday.id)"
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
            <EmptyState v-if="holidays.length === 0" title="No holidays" description="Add public/company holidays so attendance summaries treat them correctly." />
        </Card>
    </AppLayout>
</template>
