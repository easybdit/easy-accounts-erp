<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    devices: Array,
});

function testConnection(device) {
    router.post(route('hr.attendance-devices.test', device.id), {}, { preserveScroll: true });
}

function syncNow(device) {
    router.post(route('hr.attendance-devices.pull', device.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Attendance Devices" />

    <AppLayout :breadcrumbs="[{ label: 'HR' }, { label: 'Attendance Devices' }]">
        <template #header>
            <PageHeader title="Attendance Devices">
                <template #actions>
                    <Link :href="route('hr.attendance-devices.create')">
                        <PrimaryButton>New Device</PrimaryButton>
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
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Mode</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Address</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Last Synced</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="device in devices" :key="device.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ device.name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 capitalize">{{ device.connection_mode }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                {{ device.ip ? `${device.ip}:${device.port ?? 4370}` : (device.serial_number ?? '—') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ device.last_synced_at ?? 'Never' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Badge :variant="device.status === 'active' ? (device.is_online ? 'success' : 'neutral') : 'danger'">
                                    {{ device.status === 'active' ? (device.is_online ? 'Online' : 'Active') : 'Inactive' }}
                                </Badge>
                                <span v-if="device.sync_fail_count > 0" class="ml-1 text-xs text-red-500">({{ device.sync_fail_count }} failed)</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <SecondaryButton v-if="device.ip" class="mr-2" @click="testConnection(device)">Test</SecondaryButton>
                                <SecondaryButton v-if="device.ip" class="mr-2" @click="syncNow(device)">Sync Now</SecondaryButton>
                                <Link :href="route('hr.attendance-devices.edit', device.id)" class="mr-2 text-indigo-600 hover:text-indigo-900">Edit</Link>
                                <Link :href="route('hr.attendance-devices.destroy', device.id)" method="delete" as="button" class="text-red-600 hover:text-red-900">
                                    Delete
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState
                v-if="devices.length === 0"
                title="No attendance devices"
                description="Add a biometric device by its IP address (pull mode) to sync attendance automatically."
            />
        </Card>
    </AppLayout>
</template>
