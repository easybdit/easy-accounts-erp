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
    shift: Object,
});

const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

const form = useForm({
    name: props.shift.name,
    start_time: props.shift.start_time?.slice(0, 5),
    end_time: props.shift.end_time?.slice(0, 5),
    late_grace_minutes: props.shift.late_grace_minutes,
    off_days: props.shift.off_days ?? [],
});

function submit() {
    form.put(route('hr.shifts.update', props.shift.id));
}
</script>

<template>
    <Head title="Edit Shift" />

    <AppLayout
        :breadcrumbs="[
            { label: 'HR' },
            { label: 'Shifts', href: route('hr.shifts.index') },
            { label: shift.name },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit Shift — ${shift.name}`" />
        </template>

        <form class="max-w-xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="start_time" value="Start Time" />
                        <TextInput id="start_time" v-model="form.start_time" type="time" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.start_time" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="end_time" value="End Time" />
                        <TextInput id="end_time" v-model="form.end_time" type="time" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.end_time" class="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel for="late_grace_minutes" value="Late Grace (minutes)" />
                    <TextInput id="late_grace_minutes" v-model="form.late_grace_minutes" type="number" min="0" class="mt-1 block w-full" />
                    <InputError :message="form.errors.late_grace_minutes" class="mt-2" />
                </div>

                <div>
                    <InputLabel value="Off Days" />
                    <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <label v-for="day in days" :key="day" class="flex items-center gap-2 text-sm text-gray-700">
                            <input v-model="form.off_days" type="checkbox" :value="day" class="rounded border-gray-300" />
                            {{ day }}
                        </label>
                    </div>
                    <InputError :message="form.errors.off_days" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('hr.shifts.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
