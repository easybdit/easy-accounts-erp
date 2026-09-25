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
    employees: Array,
});

const form = useForm({
    employee_id: '',
    date: '',
    is_payable: true,
    payment_amount: '',
    note: '',
});

const selectedEmployee = computed(() => props.employees.find((e) => e.id === form.employee_id));

function submit() {
    form.post(route('hr.special-working-days.store'));
}
</script>

<template>
    <Head title="New Special Working Day" />

    <AppLayout
        :breadcrumbs="[
            { label: 'HR' },
            { label: 'Special Working Days', href: route('hr.special-working-days.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Special Working Day" />
        </template>

        <form class="max-w-xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div>
                    <InputLabel for="employee_id" value="Employee" />
                    <select
                        id="employee_id"
                        v-model="form.employee_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="">Select an employee</option>
                        <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                            {{ employee.name }} ({{ employee.employee_code }}) — Grade {{ employee.grade ?? 'none' }}
                        </option>
                    </select>
                    <InputError :message="form.errors.employee_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="date" value="Date" />
                    <TextInput id="date" v-model="form.date" type="date" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.date" class="mt-2" />
                    <p class="mt-1 text-xs text-gray-500">Whether this counts as a "holiday" or "day off" type is detected automatically from the date.</p>
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_payable" v-model="form.is_payable" type="checkbox" class="rounded border-gray-300" />
                    <InputLabel for="is_payable" value="Payable" />
                </div>

                <div v-if="form.is_payable">
                    <InputLabel for="payment_amount" value="Payment Amount" />
                    <TextInput
                        id="payment_amount"
                        v-model="form.payment_amount"
                        type="number"
                        step="0.01"
                        min="0"
                        :placeholder="selectedEmployee?.grade ? `Leave blank to use Grade ${selectedEmployee.grade}'s configured rate` : 'Leave blank to use the daily rate'"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.payment_amount" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="note" value="Note (optional)" />
                    <textarea
                        id="note"
                        v-model="form.note"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError :message="form.errors.note" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('hr.special-working-days.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
