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
    holiday: Object,
});

const form = useForm({
    name: props.holiday.name,
    date: props.holiday.date,
    is_recurring_yearly: props.holiday.is_recurring_yearly,
});

function submit() {
    form.put(route('hr.holidays.update', props.holiday.id));
}
</script>

<template>
    <Head title="Edit Holiday" />

    <AppLayout
        :breadcrumbs="[
            { label: 'HR' },
            { label: 'Holidays', href: route('hr.holidays.index') },
            { label: holiday.name },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit Holiday — ${holiday.name}`" />
        </template>

        <form class="max-w-xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="date" value="Date" />
                    <TextInput id="date" v-model="form.date" type="date" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.date" class="mt-2" />
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_recurring_yearly" v-model="form.is_recurring_yearly" type="checkbox" class="rounded border-gray-300" />
                    <InputLabel for="is_recurring_yearly" value="Repeats every year on this month/day" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('hr.holidays.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
