<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

defineProps({
    leaveTypes: Array,
});

const form = useForm({
    leave_type_id: '',
    start_date: '',
    end_date: '',
    reason: '',
});

function submit() {
    form.post(route('hr.my-leaves.store'));
}
</script>

<template>
    <Head title="Apply for Leave" />

    <AppLayout
        :breadcrumbs="[
            { label: 'HR' },
            { label: 'My Leave', href: route('hr.my-leaves.index') },
            { label: 'Apply' },
        ]"
    >
        <template #header>
            <PageHeader title="Apply for Leave" />
        </template>

        <form class="max-w-xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div>
                    <InputLabel for="leave_type_id" value="Leave Type" />
                    <select
                        id="leave_type_id"
                        v-model="form.leave_type_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">Select a leave type</option>
                        <option v-for="leaveType in leaveTypes" :key="leaveType.id" :value="leaveType.id">
                            {{ leaveType.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.leave_type_id" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="start_date" value="Start Date" />
                        <TextInput id="start_date" v-model="form.start_date" type="date" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.start_date" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="end_date" value="End Date" />
                        <TextInput id="end_date" v-model="form.end_date" type="date" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.end_date" class="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel for="reason" value="Reason" />
                    <textarea
                        id="reason"
                        v-model="form.reason"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError :message="form.errors.reason" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('hr.my-leaves.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Submit Request</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
