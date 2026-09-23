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

const props = defineProps({
    role: Object,
    permissions: Array,
});

const isAdministrator = props.role.name === 'Administrator';

const form = useForm({
    name: props.role.name,
    permissions: [...props.role.permissions],
});

function submit() {
    form.put(route('security.roles.update', props.role.id));
}
</script>

<template>
    <Head title="Edit Role" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Security' },
            { label: 'Roles', href: route('security.roles.index') },
            { label: role.name },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit Role — ${role.name}`" />
        </template>

        <div
            v-if="isAdministrator"
            class="mb-4 max-w-3xl rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-700"
        >
            The Administrator role always has every permission; changes to its permission list will be overwritten.
        </div>

        <form class="max-w-3xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel value="Permissions" />
                    <InputError :message="form.errors.permissions" class="mt-2" />
                    <PermissionPicker v-model="form.permissions" :permissions="permissions" :disabled="isAdministrator" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('security.roles.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
