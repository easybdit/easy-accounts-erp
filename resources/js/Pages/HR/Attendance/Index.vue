<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    employees: Array,
    selectedEmployeeId: Number,
    punches: Array,
});

const form = useForm({
    employee_id: '',
    type: 'check_in',
    date: new Date().toISOString().slice(0, 10),
    time: '09:00',
});

function record() {
    form.post(route('hr.attendance.store'), { preserveScroll: true });
}

function filterByEmployee(event) {
    router.get(route('hr.attendance.index'), { employee_id: event.target.value || undefined }, { preserveState: true });
}
</script>

<template>
    <Head title="Attendance" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'Attendance' }]">
        <template #header>
            <PageHeader title="Attendance" />
        </template>

        <Card class="mb-6">
            <h3 class="mb-3 text-sm font-medium text-gray-700">Record a Punch</h3>
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <InputLabel value="Employee" />
                    <select
                        v-model="form.employee_id"
                        class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">Select an employee</option>
                        <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                            {{ employee.name }} ({{ employee.employee_code }})
                        </option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Type" />
                    <select
                        v-model="form.type"
                        class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="check_in">Check In</option>
                        <option value="check_out">Check Out</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Date" />
                    <TextInput v-model="form.date" type="date" class="mt-1 w-40" />
                </div>
                <div>
                    <InputLabel value="Time" />
                    <TextInput v-model="form.time" type="time" class="mt-1 w-32" />
                </div>
                <PrimaryButton :disabled="!form.employee_id" :loading="form.processing" @click="record">Record</PrimaryButton>
            </div>
            <InputError :message="form.errors.employee_id" class="mt-2" />
        </Card>

        <Card>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-medium text-gray-700">Recent Punches</h3>
                <select
                    :value="selectedEmployeeId ?? ''"
                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    @change="filterByEmployee"
                >
                    <option value="">All employees</option>
                    <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                        {{ employee.name }} ({{ employee.employee_code }})
                    </option>
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Time</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Source</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="punch in punches" :key="punch.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                {{ punch.employee?.name }} ({{ punch.employee?.employee_code }})
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="punch.type === 'check_in' ? 'success' : 'neutral'">
                                    {{ punch.type === 'check_in' ? 'Check In' : 'Check Out' }}
                                </Badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ punch.time }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ punch.is_manual ? 'Manual' : punch.source }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-if="punches.length === 0" title="No attendance recorded" description="Record a punch above, or connect a biometric device." />
        </Card>
    </AppLayout>
</template>
