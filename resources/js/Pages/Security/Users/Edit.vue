<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Avatar from '@/Components/Avatar.vue';

const props = defineProps({
    targetUser: Object,
    roles: Array,
});

const page = usePage();

const form = useForm({
    name: props.targetUser.name,
    email: props.targetUser.email,
    password: '',
    password_confirmation: '',
    roles: [...props.targetUser.roles],
});

function submit() {
    form.put(route('security.users.update', props.targetUser.id), {
        onSuccess: () => {
            form.password = '';
            form.password_confirmation = '';
        },
    });
}

function unlock() {
    router.post(route('security.users.unlock', props.targetUser.id), {}, { preserveScroll: true });
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

        <div
            v-if="targetUser.is_locked"
            class="mb-4 flex max-w-xl items-center justify-between gap-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            <span>
                Locked out after {{ targetUser.failed_login_attempts }} failed login attempt(s), until
                {{ new Date(targetUser.locked_until).toLocaleString() }}.
            </span>
            <SecondaryButton type="button" @click="unlock">Unlock Now</SecondaryButton>
        </div>
        <div
            v-else-if="targetUser.failed_login_attempts > 0"
            class="mb-4 max-w-xl rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-700"
        >
            {{ targetUser.failed_login_attempts }} recent failed login attempt(s) — not yet locked.
        </div>

        <form class="max-w-xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <Avatar :name="targetUser.name" :photo-url="targetUser.profile_photo_url" size="lg" />
                    <p class="text-xs text-gray-400">Users set their own photo from their Profile page.</p>
                </div>

                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="email" value="Email" />
                    <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required autocomplete="username" />
                    <InputError :message="form.errors.email" class="mt-2" />
                </div>

                <div class="border-t border-gray-100 pt-6">
                    <InputLabel value="Reset Password" />
                    <p class="mt-1 text-xs text-gray-400">Leave both fields blank to keep the user's current password.</p>

                    <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="password" value="New Password" />
                            <TextInput
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                            />
                            <InputError :message="form.errors.password" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="password_confirmation" value="Confirm New Password" />
                            <TextInput
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                            />
                            <InputError :message="form.errors.password_confirmation" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-6">
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
