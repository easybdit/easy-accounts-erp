<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    departments: Array,
    designations: Array,
});

const form = useForm({
    employee_code: '',
    name: '',
    email: '',
    phone: '',
    department_id: '',
    designation_id: '',
    basic_salary: '',
    allowances: [],
    joined_at: '',
    status: 'active',
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
    })).post(route('hr.employees.store'));
}
</script>

<template>
    <Head title="New Employee" />

    <AppLayout
        :breadcrumbs="[
            { label: 'HR' },
            { label: 'Employees', href: route('hr.employees.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Employee" />
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
                <PrimaryButton :loading="form.processing">Save Employee</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
