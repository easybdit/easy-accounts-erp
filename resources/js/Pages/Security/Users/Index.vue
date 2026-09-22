<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

defineProps({
    users: Array,
});

const page = usePage();

function can(permission) {
    return page.props.auth.permissions?.includes(permission) ?? false;
}
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="[{ label: 'Security' }, { label: 'Users' }]">
        <template #header>
            <PageHeader title="Users" />
        </template>

        <div
            v-if="page.props.flash?.success"
            class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700"
        >
            {{ page.props.flash.success }}
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Roles</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="user in users" :key="user.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ user.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ user.email }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                            <span v-if="user.roles.length === 0" class="text-gray-400">No roles</span>
                            <span
                                v-for="role in user.roles"
                                :key="role.id"
                                class="mr-1 inline-block rounded-full bg-indigo-100 px-2 py-1 text-xs font-medium text-indigo-700"
                            >
                                {{ role.name }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <Link v-if="can('users.manage')" :href="route('security.users.edit', user.id)" class="text-indigo-600 hover:text-indigo-900">
                                Edit Roles
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="users.length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">
                            No users found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
