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
    departments: Array,
});

const form = useForm({
    name: '',
    department_id: '',
});

function submit() {
    form.post(route('hr.designations.store'));
}
</script>

<template>
    <Head title="New Designation" />

    <AppLayout
        :breadcrumbs="[
            { label: 'HR' },
            { label: 'Designations', href: route('hr.designations.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Designation" />
        </template>

        <form class="max-w-xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" type="text" placeholder="e.g. Senior Accountant" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="department_id" value="Department (optional)" />
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
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('hr.designations.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Designation</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
