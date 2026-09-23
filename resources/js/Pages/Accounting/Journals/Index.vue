<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    journals: Object,
    filters: Object,
});

const search = ref(props.filters.search ?? '');

watch(search, (value) => {
    router.get(
        route('accounting.journals.index'),
        { search: value || undefined },
        { preserveState: true, replace: true }
    );
});
</script>

<template>
    <Head title="Journal" />

    <AppLayout :breadcrumbs="[{ label: 'Accounting' }, { label: 'Journal' }]">
        <template #header>
            <PageHeader title="Journal">
                <template #actions>
                    <Link :href="route('accounting.journals.create')">
                        <PrimaryButton>New Journal Entry</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="mb-4">
            <label for="search" class="sr-only">Search journal entries</label>
            <input
                id="search"
                v-model="search"
                type="text"
                placeholder="Search by reference or description..."
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs"
            />
        </div>

        <Card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reference</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Total Debit</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Total Credit</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="journal in journals.data" :key="journal.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ journal.date }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ journal.reference ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ journal.description ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ journal.total_debit }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ journal.total_credit }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <Link :href="route('accounting.journals.show', journal.id)" class="text-indigo-600 hover:text-indigo-900">
                                    View
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-if="journals.data.length === 0" title="No journal entries found" description="Post your first journal entry to see it here." />
        </Card>

        <div v-if="journals.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, index) in journals.links"
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
