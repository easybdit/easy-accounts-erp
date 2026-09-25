<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    employee: Object,
    slips: Array,
});
</script>

<template>
    <Head title="My Payslips" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'My Payslips' }]">
        <template #header>
            <PageHeader title="My Payslips" />
        </template>

        <EmptyState
            v-if="!employee"
            title="Your account isn't linked to an employee record"
            description="Ask an administrator to link your login to an employee before payslips can be shown here."
        />

        <Card v-else>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Period</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Net Salary</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="slip in slips" :key="slip.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ slip.year }}-{{ String(slip.month).padStart(2, '0') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-900">{{ slip.net_salary }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <a :href="route('hr.my-payslips.pdf', slip.id)">
                                    <SecondaryButton>Download PDF</SecondaryButton>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-if="slips.length === 0" title="No payslips yet" description="Your salary slips will appear here once generated." />
        </Card>
    </AppLayout>
</template>
