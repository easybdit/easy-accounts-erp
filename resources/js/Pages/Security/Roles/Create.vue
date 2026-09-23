<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PermissionPicker from '@/Components/PermissionPicker.vue';

defineProps({
    permissions: Array,
});

const form = useForm({
    name: '',
    permissions: [],
});

function submit() {
    form.post(route('security.roles.store'));
}
</script>

<template>
    <Head title="New Role" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Security' },
            { label: 'Roles', href: route('security.roles.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Role" />
        </template>

        <form class="max-w-3xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" type="text" placeholder="e.g. Accountant" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel value="Permissions" />
                    <InputError :message="form.errors.permissions" class="mt-2" />
                    <PermissionPicker v-model="form.permissions" :permissions="permissions" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('security.roles.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Role</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
