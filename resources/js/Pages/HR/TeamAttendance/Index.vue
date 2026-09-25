<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    isDepartmentHead: Boolean,
    rows: Array,
    year: Number,
    month: Number,
});

const year = ref(props.year);
const month = ref(props.month);

function apply() {
    router.get(route('hr.team-attendance.index'), { year: year.value, month: month.value }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="My Team Attendance" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'My Team Attendance' }]">
        <template #header>
            <PageHeader title="My Team Attendance" />
        </template>

        <EmptyState
            v-if="!isDepartmentHead"
            title="You don't head any department"
            description="This page is for department heads to review their team's attendance."
        />

        <template v-else>
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

            <Card>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Present</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Absent</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Late</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Leave</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="row in rows" :key="row.employee_code">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                    {{ row.employee_name }} <span class="text-gray-400">({{ row.employee_code }})</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ row.present }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ row.absent }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ row.late }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ row.leave }}</td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No attendance summarized for this period.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </template>
    </AppLayout>
</template>
