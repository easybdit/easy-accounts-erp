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
    department: Object,
});

const form = useForm({
    name: props.department.name,
    description: props.department.description,
});

function submit() {
    form.put(route('hr.departments.update', props.department.id));
}
</script>

<template>
    <Head title="Edit Department" />

    <AppLayout
        :breadcrumbs="[
            { label: 'HR' },
            { label: 'Departments', href: route('hr.departments.index') },
            { label: department.name },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit Department — ${department.name}`" />
        </template>

        <form class="max-w-xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="description" value="Description" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('hr.departments.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
