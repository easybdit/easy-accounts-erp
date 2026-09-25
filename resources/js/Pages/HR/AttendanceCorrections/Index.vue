<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    corrections: Array,
});

function badgeVariant(status) {
    if (status === 'approved') return 'success';
    if (status === 'rejected') return 'danger';
    return 'warning';
}

function approve(correction) {
    router.post(route('hr.attendance-corrections.approve', correction.id));
}

function reject(correction) {
    router.post(route('hr.attendance-corrections.reject', correction.id));
}
</script>

<template>
    <Head title="Attendance Corrections" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'Attendance Corrections' }]">
        <template #header>
            <PageHeader title="Attendance Corrections" />
        </template>

        <Card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Requested In / Out</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reason</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="correction in corrections" :key="correction.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                {{ correction.employee?.name }} <span class="text-gray-400">({{ correction.employee?.employee_code }})</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ correction.date }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                {{ correction.requested_in ?? '—' }} / {{ correction.requested_out ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ correction.reason }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="badgeVariant(correction.status)">{{ correction.status }}</Badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <template v-if="correction.status === 'pending'">
                                    <SecondaryButton class="mr-2" @click="approve(correction)">Approve</SecondaryButton>
                                    <SecondaryButton @click="reject(correction)">Reject</SecondaryButton>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-if="corrections.length === 0" title="No correction requests" description="Employee-submitted attendance corrections will appear here." />
        </Card>
    </AppLayout>
</template>
