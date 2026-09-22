<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    role: Object,
    permissions: Array,
});

const isAdministrator = props.role.name === 'Administrator';

const form = useForm({
    name: props.role.name,
    permissions: [...props.role.permissions],
});

const groupedPermissions = computed(() => {
    const groups = {};
    for (const name of props.permissions) {
        const [module] = name.split('.');
        groups[module] ??= [];
        groups[module].push(name);
    }
    return groups;
});

function submit() {
    form.put(route('security.roles.update', props.role.id));
}
</script>

<template>
    <Head title="Edit Role" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Security' },
            { label: 'Roles', href: route('security.roles.index') },
            { label: role.name },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit Role — ${role.name}`" />
        </template>

        <div
            v-if="isAdministrator"
            class="mb-4 max-w-3xl rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-700"
        >
            The Administrator role always has every permission; changes to its permission list will be overwritten.
        </div>

        <form class="max-w-3xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel value="Permissions" />
                    <InputError :message="form.errors.permissions" class="mt-2" />
                    <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div
                            v-for="(names, module) in groupedPermissions"
                            :key="module"
                            class="rounded-md border border-gray-200 p-3"
                        >
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ module }}</p>
                            <div class="space-y-1">
                                <label v-for="name in names" :key="name" class="flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        v-model="form.permissions"
                                        type="checkbox"
                                        :value="name"
                                        :disabled="isAdministrator"
                                        class="rounded border-gray-300"
                                    />
                                    {{ name }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('security.roles.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
