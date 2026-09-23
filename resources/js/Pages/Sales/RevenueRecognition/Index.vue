<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    schedules: Object,
    filters: Object,
});

const status = ref(props.filters.status ?? '');

watch(status, (value) => {
    router.get(
        route('sales.revenue-recognition.index'),
        { status: value || undefined },
        { preserveState: true, replace: true }
    );
});
</script>

<template>
    <Head title="Deferred Revenue" />

    <AppLayout :breadcrumbs="[{ label: 'Sales' }, { label: 'Deferred Revenue' }]">
        <template #header>
            <PageHeader title="Deferred Revenue" />
        </template>

        <div class="mb-4">
            <label for="status" class="sr-only">Filter by status</label>
            <select
                id="status"
                v-model="status"
                class="block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="completed">Completed</option>
            </select>
        </div>

        <Card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Invoice</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Customer</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Line</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Recognized</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Next Period</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="schedule in schedules.data" :key="schedule.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                <Link :href="route('sales.revenue-recognition.show', schedule.id)" class="text-indigo-600 hover:text-indigo-900">
                                    {{ schedule.invoice_number }}
                                </Link>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ schedule.customer }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ schedule.description }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ schedule.total_amount }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">
                                {{ schedule.recognized }} ({{ schedule.months_recognized }}/{{ schedule.months_total }})
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ schedule.next_period_date ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="schedule.status === 'completed' ? 'success' : 'info'" class="capitalize">
                                    {{ schedule.status }}
                                </Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-if="schedules.data.length === 0" title="No deferred revenue schedules" description="Mark an invoice line as deferred revenue to see it tracked here." />
        </Card>

        <div v-if="schedules.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, index) in schedules.links"
                :key="index"
                :href="link.url ?? '#'"
                v-html="link.label"
                class="rounded-md px-3 py-1 text-sm"
                :class="[
                    link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                    !link.url ? 'pointer-events-none opacity-50' : '',
                ]"
            />
        </div>
    </AppLayout>
</template>
