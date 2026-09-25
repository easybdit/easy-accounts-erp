<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    leaveTypes: Array,
});
</script>

<template>
    <Head title="Leave Types" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'Leave Types' }]">
        <template #header>
            <PageHeader title="Leave Types">
                <template #actions>
                    <Link :href="route('hr.leave-types.create')">
                        <PrimaryButton>New Leave Type</PrimaryButton>
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
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Days Allowed / Year</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="leaveType in leaveTypes" :key="leaveType.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ leaveType.name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ leaveType.days_allowed_per_year ?? 'Unlimited' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <Link :href="route('hr.leave-types.edit', leaveType.id)" class="mr-3 text-indigo-600 hover:text-indigo-900">
                                    Edit
                                </Link>
                                <Link
                                    :href="route('hr.leave-types.destroy', leaveType.id)"
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
                v-if="leaveTypes.length === 0"
                title="No leave types configured"
                description="Add leave types (e.g. Annual, Sick, Casual) so employees can apply for leave."
            />
        </Card>
    </AppLayout>
</template>
