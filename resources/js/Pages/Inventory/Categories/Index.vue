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
    <Head title="Product Categories" />

    <AppLayout :breadcrumbs="[{ label: 'Inventory' }, { label: 'Categories' }]">
        <template #header>
            <PageHeader title="Product Categories">
                <template #actions>
                    <Link :href="route('inventory.categories.create')">
                        <PrimaryButton>New Category</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <Card>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Products</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="category in categories" :key="category.id" class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ category.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-500">{{ category.products_count }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <Badge :variant="category.is_active ? 'success' : 'neutral'">
                                {{ category.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <Link :href="route('inventory.categories.edit', category.id)" class="mr-3 text-indigo-600 hover:text-indigo-900">
                                Edit
                            </Link>
                            <Link
                                :href="route('inventory.categories.destroy', category.id)"
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
            <EmptyState v-if="categories.length === 0" title="No categories found" description="Create a category to start grouping products." />
        </Card>
    </AppLayout>
</template>
