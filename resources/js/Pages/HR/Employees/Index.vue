<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    employees: Array,
    shifts: Array,
});

const selectedIds = ref([]);
const allSelected = computed({
    get: () => props.employees.length > 0 && selectedIds.value.length === props.employees.length,
    set: (value) => {
        selectedIds.value = value ? props.employees.map((e) => e.id) : [];
    },
});

const showBulkAssign = ref(false);
const bulkForm = useForm({
    employee_ids: [],
    shift_id: '',
    start_date: '',
    end_date: '',
});

function openBulkAssign() {
    bulkForm.employee_ids = [...selectedIds.value];
    showBulkAssign.value = true;
}

function submitBulkAssign() {
    bulkForm.post(route('hr.employees.bulk-assign-shift'), {
        preserveScroll: true,
        onSuccess: () => {
            showBulkAssign.value = false;
            selectedIds.value = [];
            bulkForm.reset();
        },
    });
}
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

        <div v-if="selectedIds.length > 0" class="mb-4 flex items-center justify-between rounded-lg bg-indigo-50 px-4 py-3">
            <p class="text-sm text-indigo-700">{{ selectedIds.length }} employee(s) selected</p>
            <SecondaryButton @click="openBulkAssign">Assign Shift</SecondaryButton>
        </div>

        <Card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input v-model="allSelected" type="checkbox" class="rounded border-gray-300" />
                            </th>
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
                            <td class="px-4 py-3">
                                <input v-model="selectedIds" type="checkbox" :value="employee.id" class="rounded border-gray-300" />
                            </td>
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

        <div v-if="showBulkAssign" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 px-4">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
                <h3 class="mb-4 text-sm font-medium text-gray-700">Assign Shift to {{ bulkForm.employee_ids.length }} Employee(s)</h3>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="bulk_shift_id" value="Shift" />
                        <select
                            id="bulk_shift_id"
                            v-model="bulkForm.shift_id"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option value="">Select a shift</option>
                            <option v-for="shift in shifts" :key="shift.id" :value="shift.id">{{ shift.name }}</option>
                        </select>
                        <InputError :message="bulkForm.errors.shift_id" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="bulk_start_date" value="Start Date" />
                            <TextInput id="bulk_start_date" v-model="bulkForm.start_date" type="date" class="mt-1 block w-full" required />
                            <InputError :message="bulkForm.errors.start_date" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="bulk_end_date" value="End Date (optional)" />
                            <TextInput id="bulk_end_date" v-model="bulkForm.end_date" type="date" class="mt-1 block w-full" />
                            <InputError :message="bulkForm.errors.end_date" class="mt-2" />
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="showBulkAssign = false">Cancel</SecondaryButton>
                    <PrimaryButton :loading="bulkForm.processing" @click="submitBulkAssign">Assign</PrimaryButton>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
