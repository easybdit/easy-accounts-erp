<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    activities: Object,
    subjectTypes: Array,
    events: Array,
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

function eventBadgeVariant(event) {
    return {
        created: 'success',
        updated: 'info',
        deleted: 'danger',
        roles_updated: 'info',
    }[event] ?? 'neutral';
}

function formatEvent(event) {
    return event ? event.replace(/_/g, ' ') : event;
}

// A field-by-field diff of an activity's attribute_changes {old, attributes}
// shape — the same shape both LogsActivity's automatic tracking and the
// manual Role/Permission-change logging (RoleController, UserController)
// produce, so this renders both identically.
function diffFields(activity) {
    const old = activity.attribute_changes?.old ?? {};
    const attributes = activity.attribute_changes?.attributes ?? {};
    const keys = [...new Set([...Object.keys(old), ...Object.keys(attributes)])];

    return keys.map((key) => ({ key, old: old[key], new: attributes[key] }));
}

function added(oldValue, newValue) {
    const before = oldValue ?? [];
    return (newValue ?? []).filter((item) => !before.includes(item));
}

function removed(oldValue, newValue) {
    const after = newValue ?? [];
    return (oldValue ?? []).filter((item) => !after.includes(item));
}
</script>

<template>
    <Head title="Audit Log" />

    <AppLayout :breadcrumbs="[{ label: 'Security' }, { label: 'Audit Log' }]">
        <template #header>
            <PageHeader title="Audit Log" />
        </template>

        <div class="mb-4 flex flex-wrap gap-3">
            <div>
                <label for="subject_type" class="sr-only">Filter by record type</label>
                <select
                    id="subject_type"
                    v-model="subjectType"
                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">All record types</option>
                    <option v-for="type in subjectTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                </select>
            </div>
            <div>
                <label for="event" class="sr-only">Filter by event</label>
                <select
                    id="event"
                    v-model="event"
                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">All events</option>
                    <option v-for="eventOption in events" :key="eventOption" :value="eventOption">
                        {{ eventOption.replace(/_/g, ' ') }}
                    </option>
                </select>
            </div>
        </div>

        <Card>
            <div class="overflow-x-auto">
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
                        <tr v-for="activity in activities.data" :key="activity.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ activity.created_at }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="eventBadgeVariant(activity.event)">
                                    {{ formatEvent(activity.event) ?? activity.description }}
                                </Badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                {{ activity.subject_type }}<span v-if="activity.subject_id"> #{{ activity.subject_id }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ activity.causer ?? 'System' }}</td>
                            <td class="max-w-md px-4 py-3 text-xs text-gray-500">
                                <div v-if="diffFields(activity).length > 0" class="space-y-1.5">
                                    <div v-for="field in diffFields(activity)" :key="field.key">
                                        <template v-if="Array.isArray(field.old) || Array.isArray(field.new)">
                                            <p class="font-medium text-gray-600">{{ field.key }}</p>
                                            <p v-if="added(field.old, field.new).length > 0" class="text-green-700">
                                                + {{ added(field.old, field.new).join(', ') }}
                                            </p>
                                            <p v-if="removed(field.old, field.new).length > 0" class="text-red-700">
                                                − {{ removed(field.old, field.new).join(', ') }}
                                            </p>
                                            <p v-if="added(field.old, field.new).length === 0 && removed(field.old, field.new).length === 0" class="text-gray-400">
                                                No change
                                            </p>
                                        </template>
                                        <template v-else>
                                            <span class="font-medium text-gray-600">{{ field.key }}:</span>
                                            <span v-if="field.old !== undefined" class="text-red-600 line-through">{{ field.old }}</span>
                                            <span v-if="field.old !== undefined && field.new !== undefined"> → </span>
                                            <span v-if="field.new !== undefined" class="text-green-700">{{ field.new }}</span>
                                        </template>
                                    </div>
                                </div>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-if="activities.data.length === 0" title="No activity recorded" />
        </Card>

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
