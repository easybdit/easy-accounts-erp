<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    categories: Array,
});

const page = usePage();
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

        <div
            v-if="page.props.flash?.success"
            class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700"
        >
            {{ page.props.flash.success }}
        </div>
        <div
            v-if="$page.props.errors?.category"
            class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            {{ $page.props.errors.category }}
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
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
                    <tr v-for="category in categories" :key="category.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ category.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                            {{ category.default_account ? `${category.default_account.code} — ${category.default_account.name}` : '—' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium"
                                :class="category.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                            >
                                {{ category.is_active ? 'Active' : 'Inactive' }}
                            </span>
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
                    <tr v-if="categories.length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">
                            No categories found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
