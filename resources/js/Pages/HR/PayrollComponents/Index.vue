<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    components: Array,
});
</script>

<template>
    <Head title="Payroll Components" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'Payroll Components' }]">
        <template #header>
            <PageHeader title="Payroll Components">
                <template #actions>
                    <Link :href="route('hr.payroll-components.create')">
                        <PrimaryButton>New Component</PrimaryButton>
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
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Salary Slip Key</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Account</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="component in components" :key="component.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ component.name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="component.type === 'earning' ? 'success' : 'warning'">
                                    {{ component.type === 'earning' ? 'Earning' : 'Deduction' }}
                                </Badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ component.source_component_key }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ component.account.code }} — {{ component.account.name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="component.is_active ? 'success' : 'neutral'">
                                    {{ component.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <Link :href="route('hr.payroll-components.edit', component.id)" class="mr-3 text-indigo-600 hover:text-indigo-900">
                                    Edit
                                </Link>
                                <Link
                                    :href="route('hr.payroll-components.destroy', component.id)"
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
                v-if="components.length === 0"
                title="No payroll components configured"
                description="Map each salary slip field (basic salary, allowances, deductions, net pay) to a Chart of Accounts account before posting payroll."
            />
        </Card>
    </AppLayout>
</template>
