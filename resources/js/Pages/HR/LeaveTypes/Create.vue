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
    name: '',
    days_allowed_per_year: '',
});

function submit() {
    form.post(route('hr.leave-types.store'));
}
</script>

<template>
    <Head title="New Leave Type" />

    <AppLayout
        :breadcrumbs="[
            { label: 'HR' },
            { label: 'Leave Types', href: route('hr.leave-types.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Leave Type" />
        </template>

        <form class="max-w-xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" type="text" placeholder="e.g. Annual Leave" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="days_allowed_per_year" value="Days Allowed per Year (leave blank for unlimited)" />
                    <TextInput id="days_allowed_per_year" v-model="form.days_allowed_per_year" type="number" min="0" class="mt-1 block w-full" />
                    <InputError :message="form.errors.days_allowed_per_year" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('hr.leave-types.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Leave Type</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
