<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    entries: Object,
    events: Array,
    users: Array,
    filters: Object,
});

const event = ref(props.filters.event ?? '');
const userId = ref(props.filters.user_id ?? '');

watch([event, userId], ([eventValue, userIdValue]) => {
    router.get(
        route('security.login-history.index'),
        { event: eventValue || undefined, user_id: userIdValue || undefined },
        { preserveState: true, replace: true }
    );
});

const eventLabels = {
    login: 'Signed in',
    logout: 'Signed out',
    login_failed: 'Failed attempt',
    login_blocked_locked: 'Blocked — account locked',
    login_blocked_ip: 'Blocked — IP not whitelisted',
};

const eventVariants = {
    login: 'success',
    logout: 'neutral',
    login_failed: 'warning',
    login_blocked_locked: 'danger',
    login_blocked_ip: 'danger',
};

// A minimal, dependency-free "what browser/OS was this" summary — good
// enough for a security review, not meant to be a precise UA parser.
function device(userAgent) {
    if (!userAgent) return '—';

    const os = [
        [/windows/i, 'Windows'],
        [/mac os|macintosh/i, 'macOS'],
        [/android/i, 'Android'],
        [/iphone|ipad/i, 'iOS'],
        [/linux/i, 'Linux'],
    ].find(([re]) => re.test(userAgent))?.[1] ?? 'Unknown OS';

    const browser = [
        [/edg\//i, 'Edge'],
        [/chrome\//i, 'Chrome'],
        [/firefox\//i, 'Firefox'],
        [/safari\//i, 'Safari'],
    ].find(([re]) => re.test(userAgent))?.[1] ?? 'Unknown browser';

    return `${browser} on ${os}`;
}
</script>

<template>
    <Head title="Login History" />

    <AppLayout :breadcrumbs="[{ label: 'Security' }, { label: 'Login History' }]">
        <template #header>
            <PageHeader title="Login History" />
        </template>

        <p class="-mt-2 mb-4 text-sm text-gray-500">
            Every sign-in, sign-out, and blocked or failed login attempt, with the requesting IP address and device.
        </p>

        <div class="mb-4 flex flex-wrap gap-3">
            <div>
                <label for="event" class="sr-only">Filter by event</label>
                <select id="event" v-model="event" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All events</option>
                    <option v-for="eventOption in events" :key="eventOption" :value="eventOption">
                        {{ eventLabels[eventOption] ?? eventOption }}
                    </option>
                </select>
            </div>
            <div>
                <label for="user_id" class="sr-only">Filter by user</label>
                <select id="user_id" v-model="userId" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All users</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
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
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">IP Address</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Device</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="entry in entries.data" :key="entry.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ entry.created_at }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="eventVariants[entry.event] ?? 'neutral'">
                                    {{ eventLabels[entry.event] ?? entry.event }}
                                </Badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                <span v-if="entry.user">{{ entry.user }}</span>
                                <span v-else class="text-gray-400">{{ entry.description }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-sm text-gray-600">{{ entry.ip ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ device(entry.user_agent) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-if="entries.data.length === 0" title="No login activity recorded yet" />
        </Card>

        <div v-if="entries.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, index) in entries.links"
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
