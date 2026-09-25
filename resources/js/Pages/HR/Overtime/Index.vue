<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    records: Array,
});

function badgeVariant(status) {
    if (status === 'approved') return 'success';
    if (status === 'rejected') return 'danger';
    return 'warning';
}

function approve(record) {
    router.post(route('hr.overtime.approve', record.id));
}

function reject(record) {
    router.post(route('hr.overtime.reject', record.id));
}
</script>

<template>
    <Head title="Overtime" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'Overtime' }]">
        <template #header>
            <PageHeader title="Overtime" />
        </template>

        <Card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Hours</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Source</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="record in records" :key="record.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                {{ record.employee?.name }}
                                <span class="text-gray-400">({{ record.employee?.employee_code }})</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ record.date }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ record.ot_hours }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ record.ot_amount }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ record.source }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="badgeVariant(record.status)">{{ record.status }}</Badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <template v-if="record.status === 'pending'">
                                    <SecondaryButton class="mr-2" @click="approve(record)">Approve</SecondaryButton>
                                    <SecondaryButton @click="reject(record)">Reject</SecondaryButton>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-if="records.length === 0" title="No overtime records" description="Overtime detected from attendance will appear here for approval." />
        </Card>
    </AppLayout>
</template>
