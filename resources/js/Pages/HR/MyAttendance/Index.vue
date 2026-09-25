<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    employee: Object,
    todayFirstIn: String,
    todayLastOut: String,
    recentPunches: Array,
});

function checkIn() {
    router.post(route('hr.my-attendance.check-in'));
}

function checkOut() {
    router.post(route('hr.my-attendance.check-out'));
}
</script>

<template>
    <Head title="My Attendance" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'My Attendance' }]">
        <template #header>
            <PageHeader title="My Attendance" />
        </template>

        <EmptyState
            v-if="!employee"
            title="Your account isn't linked to an employee record"
            description="Ask an administrator to link your login to an employee before you can check in/out."
        />

        <template v-else>
            <Card class="mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Today</p>
                        <p class="text-sm text-gray-700">
                            In: <span class="font-medium">{{ todayFirstIn ?? '—' }}</span>
                            &nbsp;·&nbsp;
                            Out: <span class="font-medium">{{ todayLastOut ?? '—' }}</span>
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <PrimaryButton @click="checkIn">Check In</PrimaryButton>
                        <PrimaryButton @click="checkOut">Check Out</PrimaryButton>
                    </div>
                </div>
            </Card>

            <Card>
                <h3 class="mb-3 text-sm font-medium text-gray-700">Recent Punches</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="punch in recentPunches" :key="punch.id" class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                    {{ punch.type === 'check_in' ? 'Check In' : 'Check Out' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ punch.time }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <EmptyState v-if="recentPunches.length === 0" title="No punches yet" description="Use Check In / Check Out above." />
            </Card>
        </template>
    </AppLayout>
</template>
