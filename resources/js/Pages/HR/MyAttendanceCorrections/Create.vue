<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const form = useForm({
    date: '',
    requested_in: '',
    requested_out: '',
    reason: '',
});

function submit() {
    form.post(route('hr.my-attendance-corrections.store'));
}
</script>

<template>
    <Head title="Request Attendance Correction" />

    <AppLayout
        :breadcrumbs="[
            { label: 'HR' },
            { label: 'My Attendance Corrections', href: route('hr.my-attendance-corrections.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="Request Attendance Correction" />
        </template>

        <form class="max-w-xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div>
                    <InputLabel for="date" value="Date" />
                    <TextInput id="date" v-model="form.date" type="date" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.date" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="requested_in" value="Check In Time" />
                        <TextInput id="requested_in" v-model="form.requested_in" type="time" class="mt-1 block w-full" />
                        <InputError :message="form.errors.requested_in" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="requested_out" value="Check Out Time" />
                        <TextInput id="requested_out" v-model="form.requested_out" type="time" class="mt-1 block w-full" />
                        <InputError :message="form.errors.requested_out" class="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel for="reason" value="Reason" />
                    <textarea
                        id="reason"
                        v-model="form.reason"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    />
                    <InputError :message="form.errors.reason" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('hr.my-attendance-corrections.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Submit Request</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
