<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    slips: Array,
    year: Number,
    month: Number,
});

const generateForm = useForm({
    year: props.year,
    month: props.month,
});

function generate() {
    generateForm.post(route('hr.payroll.generate'));
}

function changePeriod() {
    router.get(route('hr.payroll.index'), { year: generateForm.year, month: generateForm.month }, { preserveState: true });
}

function postToAccounts(slip) {
    router.post(route('hr.payroll.post', slip.id));
}
</script>

<template>
    <Head title="Payroll" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'Payroll' }]">
        <template #header>
            <PageHeader title="Payroll">
                <template #actions>
                    <Link :href="route('hr.payroll-components.index')">
                        <SecondaryButton>Payroll Components</SecondaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <Card class="mb-6">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-medium uppercase tracking-wider text-gray-500">Year</label>
                    <input v-model.number="generateForm.year" type="number" class="mt-1 w-28 rounded-md border-gray-300 text-sm shadow-sm" @change="changePeriod" />
                </div>
                <div>
                    <label class="block text-xs font-medium uppercase tracking-wider text-gray-500">Month</label>
                    <input v-model.number="generateForm.month" type="number" min="1" max="12" class="mt-1 w-20 rounded-md border-gray-300 text-sm shadow-sm" @change="changePeriod" />
                </div>
                <PrimaryButton :loading="generateForm.processing" @click="generate">Generate for Month</PrimaryButton>
            </div>
        </Card>

        <Card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Gross</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Deduction</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Net</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="slip in slips" :key="slip.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                {{ slip.employee?.name }}
                                <span class="text-gray-400">({{ slip.employee?.employee_code }})</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ slip.gross_salary }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ slip.deduction_amount }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-900">{{ slip.net_salary }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="slip.is_posted ? 'success' : 'neutral'">
                                    {{ slip.is_posted ? 'Posted' : 'Not Posted' }}
                                </Badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <SecondaryButton v-if="!slip.is_posted" @click="postToAccounts(slip)">Post to Accounts</SecondaryButton>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState
                v-if="slips.length === 0"
                title="No salary slips for this period"
                description="Generate salary slips for the selected month to review and post them to accounts."
            />
        </Card>
    </AppLayout>
</template>
