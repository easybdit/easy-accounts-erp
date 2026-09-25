<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    specialWorkingDays: Array,
});
</script>

<template>
    <Head title="Special Working Days" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'Special Working Days' }]">
        <template #header>
            <PageHeader title="Special Working Days">
                <template #actions>
                    <Link :href="route('hr.special-working-days.create')">
                        <PrimaryButton>New Entry</PrimaryButton>
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
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Payment</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="day in specialWorkingDays" :key="day.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                {{ day.employee?.name }} <span class="text-gray-400">({{ day.employee?.employee_code }})</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ day.date }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge variant="info">{{ day.type }}</Badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">
                                {{ day.is_payable ? (day.payment_amount ?? '—') : 'Unpaid' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <Link :href="route('hr.special-working-days.edit', day.id)" class="mr-3 text-indigo-600 hover:text-indigo-900">
                                    Edit
                                </Link>
                                <Link
                                    :href="route('hr.special-working-days.destroy', day.id)"
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
                v-if="specialWorkingDays.length === 0"
                title="No special working days"
                description="Record a day an employee was asked to work despite it normally being a day off or holiday, so payroll pays for it correctly."
            />
        </Card>
    </AppLayout>
</template>
