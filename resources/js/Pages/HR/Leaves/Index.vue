<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    leaves: Array,
});

function badgeVariant(status) {
    if (status === 'approved') return 'success';
    if (status === 'rejected') return 'danger';
    return 'warning';
}

function approve(leave) {
    router.post(route('hr.leaves.approve', leave.id));
}

function reject(leave) {
    router.post(route('hr.leaves.reject', leave.id));
}
</script>

<template>
    <Head title="Leave Requests" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'Leaves' }]">
        <template #header>
            <PageHeader title="Leave Requests">
                <template #actions>
                    <Link :href="route('hr.leave-types.index')">
                        <SecondaryButton>Leave Types</SecondaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <Card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Dates</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reason</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="leave in leaves" :key="leave.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                {{ leave.employee?.name }}
                                <span class="text-gray-400">({{ leave.employee?.employee_code }})</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ leave.leave_type?.name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ leave.start_date }} – {{ leave.end_date }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ leave.reason ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="badgeVariant(leave.status)">{{ leave.status }}</Badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <template v-if="leave.status === 'pending'">
                                    <SecondaryButton class="mr-2" @click="approve(leave)">Approve</SecondaryButton>
                                    <SecondaryButton @click="reject(leave)">Reject</SecondaryButton>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-if="leaves.length === 0" title="No leave requests" description="Employee leave requests will appear here." />
        </Card>
    </AppLayout>
</template>
