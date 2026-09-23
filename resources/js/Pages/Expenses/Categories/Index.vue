<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    categories: Array,
});
</script>

<template>
    <Head title="Expense Categories" />

    <AppLayout :breadcrumbs="[{ label: 'Expenses' }, { label: 'Categories' }]">
        <template #header>
            <PageHeader title="Expense Categories">
                <template #actions>
                    <Link :href="route('expenses.categories.create')">
                        <PrimaryButton>New Category</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <Card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Default Account</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="category in categories" :key="category.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ category.name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                {{ category.default_account ? `${category.default_account.code} — ${category.default_account.name}` : '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="category.is_active ? 'success' : 'neutral'">
                                    {{ category.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <Link :href="route('expenses.categories.edit', category.id)" class="mr-3 text-indigo-600 hover:text-indigo-900">
                                    Edit
                                </Link>
                                <Link
                                    :href="route('expenses.categories.destroy', category.id)"
                                    method="delete"
                                    as="button"
                                    class="text-red-600 hover:text-red-900"
                                >
                                    Delete
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-if="categories.length === 0" title="No categories found" description="Create a category to start grouping expenses." />
        </Card>
    </AppLayout>
</template>
