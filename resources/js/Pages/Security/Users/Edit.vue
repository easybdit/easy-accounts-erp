<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    targetUser: Object,
    roles: Array,
});

const page = usePage();

const form = useForm({
    roles: [...props.targetUser.roles],
});

function submit() {
    form.put(route('security.users.update', props.targetUser.id));
}
</script>

<template>
    <Head title="Edit User" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Security' },
            { label: 'Users', href: route('security.users.index') },
            { label: targetUser.name },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit User — ${targetUser.name}`" />
        </template>

        <div
            v-if="targetUser.id === page.props.auth.user.id"
            class="mb-4 max-w-xl rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-700"
        >
            This is your own account. You cannot remove your own Administrator role.
        </div>

        <form class="max-w-xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div>
                    <InputLabel value="Email" />
                    <p class="mt-1 text-sm text-gray-700">{{ targetUser.email }}</p>
                </div>

                <div>
                    <InputLabel value="Roles" />
                    <InputError :message="form.errors.roles" class="mt-2" />
                    <div class="mt-2 space-y-1">
                        <label v-for="roleName in roles" :key="roleName" class="flex items-center gap-2 text-sm text-gray-700">
                            <input
                                v-model="form.roles"
                                type="checkbox"
                                :value="roleName"
                                class="rounded border-gray-300"
                            />
                            {{ roleName }}
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('security.users.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
