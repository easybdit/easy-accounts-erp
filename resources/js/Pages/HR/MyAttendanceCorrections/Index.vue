<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    employee: Object,
    corrections: Array,
});

function badgeVariant(status) {
    if (status === 'approved') return 'success';
    if (status === 'rejected') return 'danger';
    return 'warning';
}
</script>

<template>
    <Head title="My Attendance Corrections" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'My Attendance Corrections' }]">
        <template #header>
            <PageHeader title="My Attendance Corrections">
                <template #actions>
                    <Link v-if="employee" :href="route('hr.my-attendance-corrections.create')">
                        <PrimaryButton>Request Correction</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <EmptyState
            v-if="!employee"
            title="Your account isn't linked to an employee record"
            description="Ask an administrator to link your login to an employee first."
        />

        <Card v-else>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Requested In / Out</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reason</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="correction in corrections" :key="correction.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ correction.date }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                {{ correction.requested_in ?? '—' }} / {{ correction.requested_out ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ correction.reason }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="badgeVariant(correction.status)">{{ correction.status }}</Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-if="corrections.length === 0" title="No correction requests yet" description="Request a correction using the button above." />
        </Card>
    </AppLayout>
</template>
