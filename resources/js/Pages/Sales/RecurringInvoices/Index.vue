<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    templates: Array,
});

function generate(template) {
    router.post(route('sales.recurring-invoices.generate', template.id));
}
</script>

<template>
    <Head title="Recurring Invoices" />

    <AppLayout :breadcrumbs="[{ label: 'Sales' }, { label: 'Recurring Invoices' }]">
        <template #header>
            <PageHeader title="Recurring Invoices">
                <template #actions>
                    <Link :href="route('sales.recurring-invoices.create')">
                        <PrimaryButton>New Template</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <Card>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Customer</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Lines</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Next Auto-Generation</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="template in templates" :key="template.id" class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ template.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ template.customer.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-500">{{ template.items_count }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                            {{ template.next_generation_date ?? 'Manual only' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <Badge :variant="template.is_active ? 'success' : 'neutral'">
                                {{ template.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <button
                                type="button"
                                class="mr-3 font-medium text-indigo-600 hover:text-indigo-900"
                                @click="generate(template)"
                            >
                                Generate Now
                            </button>
                            <Link :href="route('sales.recurring-invoices.edit', template.id)" class="mr-3 text-indigo-600 hover:text-indigo-900">
                                Edit
                            </Link>
                            <Link
                                :href="route('sales.recurring-invoices.destroy', template.id)"
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
            <EmptyState
                v-if="templates.length === 0"
                title="No recurring invoice templates"
                description="Save a template once, then click Generate Now whenever you need to bill it again."
            />
        </Card>
    </AppLayout>
</template>
