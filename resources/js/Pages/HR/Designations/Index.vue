<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    designations: Array,
});
</script>

<template>
    <Head title="Designations" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'Designations' }]">
        <template #header>
            <PageHeader title="Designations">
                <template #actions>
                    <Link :href="route('hr.designations.create')">
                        <PrimaryButton>New Designation</PrimaryButton>
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
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Department</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Employees</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="designation in designations" :key="designation.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ designation.name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ designation.department?.name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ designation.employees_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <Link :href="route('hr.designations.edit', designation.id)" class="mr-3 text-indigo-600 hover:text-indigo-900">
                                    Edit
                                </Link>
                                <Link
                                    :href="route('hr.designations.destroy', designation.id)"
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
            <EmptyState v-if="designations.length === 0" title="No designations" description="Add designations (job titles) to assign to employees." />
        </Card>
    </AppLayout>
</template>
