<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    roles: Array,
});

const page = usePage();

function can(permission) {
    return page.props.auth.permissions?.includes(permission) ?? false;
}
</script>

<template>
    <Head title="Roles" />

    <AppLayout :breadcrumbs="[{ label: 'Security' }, { label: 'Roles' }]">
        <template #header>
            <PageHeader title="Roles">
                <template #actions>
                    <Link v-if="can('roles.manage')" :href="route('security.roles.create')">
                        <PrimaryButton>New Role</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div
            v-if="page.props.flash?.success"
            class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700"
        >
            {{ page.props.flash.success }}
        </div>
        <div
            v-if="$page.props.errors?.role"
            class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            {{ $page.props.errors.role }}
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Permissions</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Users</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="role in roles" :key="role.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ role.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-500">{{ role.permissions_count }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-500">{{ role.users_count }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <template v-if="can('roles.manage')">
                                <Link :href="route('security.roles.edit', role.id)" class="mr-3 text-indigo-600 hover:text-indigo-900">
                                    Edit
                                </Link>
                                <Link
                                    v-if="role.name !== 'Administrator'"
                                    :href="route('security.roles.destroy', role.id)"
                                    method="delete"
                                    as="button"
                                    class="text-red-600 hover:text-red-900"
                                >
                                    Delete
                                </Link>
                            </template>
                        </td>
                    </tr>
                    <tr v-if="roles.length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">
                            No roles found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
