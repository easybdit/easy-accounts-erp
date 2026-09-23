<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

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

        <Card>
            <div class="overflow-x-auto">
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
                        <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ user.name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ user.email }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                <span v-if="user.roles.length === 0" class="text-gray-400">No roles</span>
                                <Badge v-for="role in user.roles" :key="role.id" variant="info" class="mr-1">
                                    {{ role.name }}
                                </Badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <Link v-if="can('users.manage')" :href="route('security.users.edit', user.id)" class="text-indigo-600 hover:text-indigo-900">
                                    Edit Roles
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-if="users.length === 0" title="No users found" />
        </Card>
    </AppLayout>
</template>
