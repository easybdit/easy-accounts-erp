<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    activities: Object,
    subjectTypes: Array,
    filters: Object,
});

const subjectType = ref(props.filters.subject_type ?? '');
const event = ref(props.filters.event ?? '');

watch([subjectType, event], ([subjectTypeValue, eventValue]) => {
    router.get(
        route('security.audit-log.index'),
        { subject_type: subjectTypeValue || undefined, event: eventValue || undefined },
        { preserveState: true, replace: true }
    );
});

function eventBadgeClass(event) {
    return {
        created: 'bg-green-100 text-green-700',
        updated: 'bg-blue-100 text-blue-700',
        deleted: 'bg-red-100 text-red-700',
    }[event] ?? 'bg-gray-100 text-gray-600';
}
</script>

<template>
    <Head title="Audit Log" />

    <AppLayout :breadcrumbs="[{ label: 'Security' }, { label: 'Audit Log' }]">
        <template #header>
            <PageHeader title="Audit Log" />
        </template>

        <div class="mb-4 flex flex-wrap gap-3">
            <select
                v-model="subjectType"
                class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">All record types</option>
                <option v-for="type in subjectTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
            </select>
            <select
                v-model="event"
                class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">All events</option>
                <option value="created">Created</option>
                <option value="updated">Updated</option>
                <option value="deleted">Deleted</option>
            </select>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">When</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Event</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Record</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Changes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="activity in activities.data" :key="activity.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ activity.created_at }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <span class="rounded-full px-2 py-1 text-xs font-medium" :class="eventBadgeClass(activity.event)">
                                {{ activity.event ?? activity.description }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                            {{ activity.subject_type }}<span v-if="activity.subject_id"> #{{ activity.subject_id }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ activity.causer ?? 'System' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            <pre class="max-w-md whitespace-pre-wrap break-words">{{ JSON.stringify(activity.attribute_changes, null, 2) }}</pre>
                        </td>
                    </tr>
                    <tr v-if="activities.data.length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                            No activity recorded.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="activities.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, index) in activities.links"
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
