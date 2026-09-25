<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    employees: Array,
});
</script>

<template>
    <Head title="Employees" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'Employees' }]">
        <template #header>
            <PageHeader title="Employees">
                <template #actions>
                    <Link :href="route('hr.employees.create')">
                        <PrimaryButton>New Employee</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <Card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Code</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Department</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Designation</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Basic Salary</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="employee in employees" :key="employee.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ employee.employee_code }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ employee.name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ employee.department?.name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ employee.designation_record?.name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ employee.basic_salary }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="employee.status === 'active' ? 'success' : 'neutral'">{{ employee.status }}</Badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <Link :href="route('hr.employees.edit', employee.id)" class="text-indigo-600 hover:text-indigo-900">
                                    Edit
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-if="employees.length === 0" title="No employees" description="Add employees to start using leave, overtime, and payroll." />
        </Card>
    </AppLayout>
</template>
