<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    device: Object,
});

const form = useForm({
    name: props.device.name,
    ip: props.device.ip,
    port: props.device.port,
    comm_key: props.device.comm_key,
    serial_number: props.device.serial_number,
    model: props.device.model,
    status: props.device.status,
});

function submit() {
    form.put(route('hr.attendance-devices.update', props.device.id));
}
</script>

<template>
    <Head title="Edit Attendance Device" />

    <AppLayout
        :breadcrumbs="[
            { label: 'HR' },
            { label: 'Attendance Devices', href: route('hr.attendance-devices.index') },
            { label: device.name },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit Attendance Device — ${device.name}`" />
        </template>

        <form class="max-w-xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="ip" value="IP Address" />
                        <TextInput id="ip" v-model="form.ip" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.ip" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="port" value="Port" />
                        <TextInput id="port" v-model="form.port" type="number" min="1" max="65535" class="mt-1 block w-full" />
                        <InputError :message="form.errors.port" class="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel for="comm_key" value="Comm Key / Password (if set on the device)" />
                    <TextInput id="comm_key" v-model="form.comm_key" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.comm_key" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="serial_number" value="Serial Number (push/ADMS mode only)" />
                    <TextInput id="serial_number" v-model="form.serial_number" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.serial_number" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="model" value="Model (optional)" />
                    <TextInput id="model" v-model="form.model" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.model" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="status" value="Status" />
                    <select
                        id="status"
                        v-model="form.status"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <InputError :message="form.errors.status" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('hr.attendance-devices.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
