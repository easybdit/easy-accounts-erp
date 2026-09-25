<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    shifts: Array,
});
</script>

<template>
    <Head title="Shifts" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'Shifts' }]">
        <template #header>
            <PageHeader title="Shifts">
                <template #actions>
                    <Link :href="route('hr.shifts.create')">
                        <PrimaryButton>New Shift</PrimaryButton>
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
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Hours</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Grace (min)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Off Days</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="shift in shifts" :key="shift.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ shift.name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ shift.start_time }} – {{ shift.end_time }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ shift.late_grace_minutes ?? 0 }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ (shift.off_days ?? []).join(', ') || '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <Link :href="route('hr.shifts.edit', shift.id)" class="mr-3 text-indigo-600 hover:text-indigo-900">
                                    Edit
                                </Link>
                                <Link
                                    :href="route('hr.shifts.destroy', shift.id)"
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
            <EmptyState v-if="shifts.length === 0" title="No shifts" description="Add shifts so employees can be scheduled and late/absent detection can work." />
        </Card>
    </AppLayout>
</template>
