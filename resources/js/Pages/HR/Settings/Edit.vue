<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    special_working_day_grade_rates: {
        A: props.settings.special_working_day_grade_rates?.A ?? 1000,
        B: props.settings.special_working_day_grade_rates?.B ?? 700,
        C: props.settings.special_working_day_grade_rates?.C ?? 500,
    },
    late_deduction_ratio: props.settings.late_deduction_ratio,
    late_warning_threshold: props.settings.late_warning_threshold,
    multi_step_leave_approval_enabled: props.settings.multi_step_leave_approval_enabled,
});

function submit() {
    form.put(route('hr.settings.update'));
}
</script>

<template>
    <Head title="HR Settings" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'Settings' }]">
        <template #header>
            <PageHeader title="HR Settings" />
        </template>

        <form class="max-w-xl space-y-6" @submit.prevent="submit">
            <Card>
                <h3 class="mb-3 text-sm font-medium text-gray-700">Special Working Day Pay by Grade</h3>
                <p class="mb-4 text-xs text-gray-500">
                    When HR marks a special working day for an employee without entering a specific amount, this rate
                    (based on the employee's grade) is used automatically.
                </p>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <InputLabel for="grade_a" value="Grade A" />
                        <TextInput id="grade_a" v-model="form.special_working_day_grade_rates.A" type="number" step="0.01" min="0" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <InputLabel for="grade_b" value="Grade B" />
                        <TextInput id="grade_b" v-model="form.special_working_day_grade_rates.B" type="number" step="0.01" min="0" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <InputLabel for="grade_c" value="Grade C" />
                        <TextInput id="grade_c" v-model="form.special_working_day_grade_rates.C" type="number" step="0.01" min="0" class="mt-1 block w-full" required />
                    </div>
                </div>
                <InputError :message="form.errors['special_working_day_grade_rates.A']" class="mt-2" />
                <InputError :message="form.errors['special_working_day_grade_rates.B']" class="mt-2" />
                <InputError :message="form.errors['special_working_day_grade_rates.C']" class="mt-2" />
            </Card>

            <Card>
                <h3 class="mb-3 text-sm font-medium text-gray-700">Attendance Policy</h3>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="late_deduction_ratio" value="Late arrivals per 1-day salary deduction" />
                        <TextInput
                            id="late_deduction_ratio"
                            v-model="form.late_deduction_ratio"
                            type="number"
                            min="1"
                            max="31"
                            placeholder="Leave blank to use the default (3)"
                            class="mt-1 block w-full"
                        />
                        <p class="mt-1 text-xs text-gray-500">e.g. 3 means every 3rd late day in a month costs 1 day's pay, same as an absence.</p>
                        <InputError :message="form.errors.late_deduction_ratio" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="late_warning_threshold" value="Late arrivals before a formal warning" />
                        <TextInput id="late_warning_threshold" v-model="form.late_warning_threshold" type="number" min="1" max="31" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.late_warning_threshold" class="mt-2" />
                    </div>
                </div>
            </Card>

            <Card>
                <h3 class="mb-3 text-sm font-medium text-gray-700">Leave Approval</h3>
                <div class="flex items-center gap-2">
                    <input id="multi_step_leave_approval_enabled" v-model="form.multi_step_leave_approval_enabled" type="checkbox" class="rounded border-gray-300" />
                    <InputLabel for="multi_step_leave_approval_enabled" value="Require Department Head approval before HR approval" />
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Only applies to employees whose department has a Head assigned. Otherwise leave requests go straight to HR, as before.
                </p>
            </Card>

            <div class="flex justify-end">
                <PrimaryButton :loading="form.processing">Save Settings</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
