<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    specialWorkingDay: Object,
    employees: Array,
});

const form = useForm({
    employee_id: props.specialWorkingDay.employee_id,
    date: props.specialWorkingDay.date,
    is_payable: props.specialWorkingDay.is_payable,
    payment_amount: props.specialWorkingDay.payment_amount,
    note: props.specialWorkingDay.note,
});

function submit() {
    form.put(route('hr.special-working-days.update', props.specialWorkingDay.id));
}
</script>

<template>
    <Head title="Edit Special Working Day" />

    <AppLayout
        :breadcrumbs="[
            { label: 'HR' },
            { label: 'Special Working Days', href: route('hr.special-working-days.index') },
            { label: specialWorkingDay.date },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit Special Working Day — ${specialWorkingDay.date}`" />
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
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_payable" v-model="form.is_payable" type="checkbox" class="rounded border-gray-300" />
                    <InputLabel for="is_payable" value="Payable" />
                </div>

                <div v-if="form.is_payable">
                    <InputLabel for="payment_amount" value="Payment Amount" />
                    <TextInput id="payment_amount" v-model="form.payment_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" />
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
                <PrimaryButton :loading="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
