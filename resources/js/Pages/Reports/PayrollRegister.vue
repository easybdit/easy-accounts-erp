<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ExportCsvButton from '@/Components/ExportCsvButton.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
    rows: Array,
    totals: Object,
    year: Number,
    month: Number,
});

const year = ref(props.year);
const month = ref(props.month);

function apply() {
    router.get(route('reports.payroll-register'), { year: year.value, month: month.value }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Payroll Register" />

    <AppLayout :breadcrumbs="[{ label: 'Reports', href: route('reports.index') }, { label: 'Payroll Register' }]">
        <template #header>
            <PageHeader title="Payroll Register">
                <template #actions>
                    <ExportCsvButton route-name="reports.payroll-register" :params="{ year, month }" />
                </template>
            </PageHeader>
        </template>

        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
            <div>
                <label class="block text-xs font-medium text-gray-500">Year</label>
                <input v-model.number="year" type="number" class="mt-1 block w-28 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="apply" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">Month</label>
                <input v-model.number="month" type="number" min="1" max="12" class="mt-1 block w-20 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="apply" />
            </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Gross</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Deduction</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Net</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="row in rows" :key="row.employee_code">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                {{ row.employee_name }} <span class="text-gray-400">({{ row.employee_code }})</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ row.gross }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ row.deduction }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-900">{{ row.net }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="row.is_posted ? 'success' : 'neutral'">{{ row.is_posted ? 'Posted' : 'Not Posted' }}</Badge>
                            </td>
                        </tr>
                        <tr v-if="rows.length === 0">
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No salary slips for this period.</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="rows.length > 0" class="bg-gray-50">
                        <tr class="font-semibold">
                            <td class="px-4 py-3 text-right text-sm text-gray-700">Total</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-900">{{ totals.gross }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-900">{{ totals.deduction }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-900">{{ totals.net }}</td>
                            <td />
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
