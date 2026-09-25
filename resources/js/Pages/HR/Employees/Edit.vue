<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Card from '@/Components/Card.vue';

const props = defineProps({
    employee: Object,
    shiftAssignments: Array,
    linkedUser: Object,
    availableUsers: Array,
    shifts: Array,
    departments: Array,
    designations: Array,
});

const form = useForm({
    employee_code: props.employee.employee_code,
    name: props.employee.name,
    email: props.employee.email,
    phone: props.employee.phone,
    department_id: props.employee.department_id ?? '',
    designation_id: props.employee.designation_id ?? '',
    device_user_id: props.employee.device_user_id ?? '',
    grade: props.employee.grade ?? '',
    basic_salary: props.employee.basic_salary,
    allowances: Object.entries(props.employee.allowances ?? {}).map(([key, amount]) => ({ key, amount })),
    joined_at: props.employee.joined_at ?? '',
    status: props.employee.status,
});

const filteredDesignations = computed(() =>
    form.department_id
        ? props.designations.filter((d) => d.department_id === form.department_id || d.department_id === null)
        : props.designations
);

function addAllowance() {
    form.allowances.push({ key: '', amount: '' });
}

function removeAllowance(index) {
    form.allowances.splice(index, 1);
}

function submit() {
    form.transform((data) => ({
        ...data,
        allowances: Object.fromEntries(data.allowances.filter((a) => a.key).map((a) => [a.key, a.amount])),
    })).put(route('hr.employees.update', props.employee.id));
}

const linkUserForm = useForm({ user_id: '' });

function linkUser() {
    linkUserForm.post(route('hr.employees.link-user', props.employee.id), { preserveScroll: true });
}

function unlinkUser() {
    router.delete(route('hr.employees.unlink-user', props.employee.id), { preserveScroll: true });
}

const shiftForm = useForm({ shift_id: '', start_date: '', end_date: '' });

function assignShift() {
    shiftForm.post(route('hr.employees.shifts.store', props.employee.id), {
        preserveScroll: true,
        onSuccess: () => shiftForm.reset(),
    });
}

function removeShift(assignment) {
    router.delete(route('hr.employees.shifts.destroy', [props.employee.id, assignment.id]), { preserveScroll: true });
}
</script>

<template>
    <Head title="Edit Employee" />

    <AppLayout
        :breadcrumbs="[
            { label: 'HR' },
            { label: 'Employees', href: route('hr.employees.index') },
            { label: employee.name },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit Employee — ${employee.name}`" />
        </template>

        <form class="max-w-2xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="employee_code" value="Employee Code" />
                        <TextInput id="employee_code" v-model="form.employee_code" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.employee_code" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="phone" value="Phone" />
                        <TextInput id="phone" v-model="form.phone" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.phone" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="department_id" value="Department" />
                        <select
                            id="department_id"
                            v-model="form.department_id"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">None</option>
                            <option v-for="department in departments" :key="department.id" :value="department.id">
                                {{ department.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.department_id" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="designation_id" value="Designation" />
                        <select
                            id="designation_id"
                            v-model="form.designation_id"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">None</option>
                            <option v-for="designation in filteredDesignations" :key="designation.id" :value="designation.id">
                                {{ designation.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.designation_id" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="device_user_id" value="Device PIN / User ID (optional)" />
                        <TextInput id="device_user_id" v-model="form.device_user_id" type="text" placeholder="The employee's PIN on the biometric device" class="mt-1 block w-full" />
                        <InputError :message="form.errors.device_user_id" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="grade" value="Grade (for special working day pay)" />
                        <select
                            id="grade"
                            v-model="form.grade"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">None</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                        <InputError :message="form.errors.grade" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="basic_salary" value="Basic Salary" />
                        <TextInput id="basic_salary" v-model="form.basic_salary" type="number" step="0.01" min="0" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.basic_salary" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="joined_at" value="Joined Date" />
                        <TextInput id="joined_at" v-model="form.joined_at" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.joined_at" class="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel value="Allowances" />
                    <div class="mt-2 space-y-2">
                        <div v-for="(allowance, index) in form.allowances" :key="index" class="flex items-center gap-2">
                            <TextInput v-model="allowance.key" type="text" placeholder="e.g. house_rent" class="block w-1/2" />
                            <TextInput v-model="allowance.amount" type="number" step="0.01" min="0" placeholder="Amount" class="block w-1/3" />
                            <button type="button" class="text-sm text-red-600 hover:text-red-900" @click="removeAllowance(index)">Remove</button>
                        </div>
                        <SecondaryButton type="button" @click="addAllowance">Add Allowance</SecondaryButton>
                    </div>
                    <InputError :message="form.errors.allowances" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="status" value="Status" />
                    <select
                        id="status"
                        v-model="form.status"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <InputError :message="form.errors.status" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('hr.employees.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>

        <Card class="mt-6 max-w-2xl">
            <h3 class="mb-3 text-sm font-medium text-gray-700">Login Account</h3>
            <div v-if="linkedUser" class="flex items-center justify-between">
                <p class="text-sm text-gray-700">{{ linkedUser.name }} ({{ linkedUser.email }})</p>
                <SecondaryButton @click="unlinkUser">Unlink</SecondaryButton>
            </div>
            <div v-else class="flex items-center gap-2">
                <select
                    v-model="linkUserForm.user_id"
                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">Select a user account</option>
                    <option v-for="user in availableUsers" :key="user.id" :value="user.id">{{ user.name }} ({{ user.email }})</option>
                </select>
                <PrimaryButton :disabled="!linkUserForm.user_id" @click="linkUser">Link</PrimaryButton>
            </div>
            <p class="mt-2 text-xs text-gray-500">
                Linking lets this employee log in and see their own leave balance, apply for leave, and download payslips.
            </p>
        </Card>

        <Card class="mt-6 max-w-2xl">
            <h3 class="mb-3 text-sm font-medium text-gray-700">Shift Assignments</h3>
            <table class="mb-4 min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Shift</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">From</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">To</th>
                        <th class="px-2 py-2" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="assignment in shiftAssignments" :key="assignment.id">
                        <td class="px-2 py-2 text-sm text-gray-700">{{ assignment.shift?.name }}</td>
                        <td class="px-2 py-2 text-sm text-gray-500">{{ assignment.start_date }}</td>
                        <td class="px-2 py-2 text-sm text-gray-500">{{ assignment.end_date ?? 'Ongoing' }}</td>
                        <td class="px-2 py-2 text-right text-sm">
                            <button type="button" class="text-red-600 hover:text-red-900" @click="removeShift(assignment)">Remove</button>
                        </td>
                    </tr>
                    <tr v-if="shiftAssignments.length === 0">
                        <td colspan="4" class="px-2 py-2 text-sm text-gray-400">No shift assigned yet.</td>
                    </tr>
                </tbody>
            </table>

            <div class="flex flex-wrap items-end gap-2">
                <select
                    v-model="shiftForm.shift_id"
                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">Select a shift</option>
                    <option v-for="shift in shifts" :key="shift.id" :value="shift.id">{{ shift.name }}</option>
                </select>
                <TextInput v-model="shiftForm.start_date" type="date" class="w-40" />
                <TextInput v-model="shiftForm.end_date" type="date" class="w-40" placeholder="Optional end date" />
                <SecondaryButton :disabled="!shiftForm.shift_id || !shiftForm.start_date" @click="assignShift">Assign</SecondaryButton>
            </div>
        </Card>
    </AppLayout>
</template>
