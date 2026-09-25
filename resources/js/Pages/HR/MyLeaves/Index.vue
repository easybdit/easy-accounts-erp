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
    leaves: Array,
    balances: Array,
});

function badgeVariant(status) {
    if (status === 'approved') return 'success';
    if (status === 'rejected') return 'danger';
    return 'warning';
}
</script>

<template>
    <Head title="My Leave" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'My Leave' }]">
        <template #header>
            <PageHeader title="My Leave">
                <template #actions>
                    <Link v-if="employee" :href="route('hr.my-leaves.create')">
                        <PrimaryButton>Apply for Leave</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <EmptyState
            v-if="!employee"
            title="Your account isn't linked to an employee record"
            description="Ask an administrator to link your login to an employee before you can apply for leave."
        />

        <template v-else>
            <Card class="mb-6">
                <h3 class="mb-3 text-sm font-medium text-gray-700">Leave Balance ({{ new Date().getFullYear() }})</h3>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div v-for="balance in balances" :key="balance.leave_type_id" class="rounded-md border border-gray-100 p-3">
                        <p class="text-xs text-gray-500">{{ balance.leave_type }}</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ balance.remaining ?? '∞' }}
                            <span class="text-xs font-normal text-gray-400">/ {{ balance.allowed ?? 'unlimited' }}</span>
                        </p>
                        <p class="text-xs text-gray-400">{{ balance.used }} used</p>
                    </div>
                </div>
            </Card>

            <Card>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Dates</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reason</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="leave in leaves" :key="leave.id" class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ leave.leave_type?.name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ leave.start_date }} – {{ leave.end_date }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ leave.reason ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm">
                                    <Badge :variant="badgeVariant(leave.status)">{{ leave.status }}</Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <EmptyState v-if="leaves.length === 0" title="No leave requests yet" description="Apply for leave using the button above." />
            </Card>
        </template>
    </AppLayout>
</template>
