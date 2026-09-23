<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Card from '@/Components/Card.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    settings: Object,
});

const page = usePage();

const form = useForm({
    locked_through_date: props.settings.locked_through_date ?? '',
});

function canManage() {
    return page.props.auth.permissions?.includes('settings.manage') ?? false;
}

function submit() {
    form.put(route('accounting.settings.update'));
}

function removeLock() {
    form.locked_through_date = '';
    form.put(route('accounting.settings.update'));
}
</script>

<template>
    <Head title="Period Lock" />

    <AppLayout :breadcrumbs="[{ label: 'Accounting' }, { label: 'Period Lock' }]">
        <template #header>
            <PageHeader title="Period Lock" />
        </template>

        <Card padded class="max-w-xl">
            <p class="text-sm text-gray-500">
                Once a period has been reported on and reconciled, lock it to prevent any new journal, invoice,
                bill, payment, or other posting dated on or before the lock date — including generated documents
                like depreciation and revenue recognition. Draft documents can still be created and edited; only
                posting into a locked date is blocked.
            </p>

            <div v-if="settings.locked_through_date" class="mt-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Currently locked through <strong>{{ settings.locked_through_date }}</strong>.
            </div>
            <div v-else class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">
                No lock is set — all dates are open for posting.
            </div>

            <form class="mt-6" @submit.prevent="submit">
                <InputLabel for="locked_through_date" value="Locked Through Date" />
                <TextInput
                    id="locked_through_date"
                    v-model="form.locked_through_date"
                    type="date"
                    class="mt-1 block w-full max-w-xs"
                    :disabled="!canManage()"
                />
                <p class="mt-1 text-xs text-gray-400">Leave blank and save to remove the lock entirely.</p>
                <InputError :message="form.errors.locked_through_date" class="mt-2" />

                <div v-if="canManage()" class="mt-6 flex gap-3">
                    <PrimaryButton :loading="form.processing">Save</PrimaryButton>
                    <SecondaryButton v-if="settings.locked_through_date" type="button" @click="removeLock">
                        Remove Lock
                    </SecondaryButton>
                </div>
            </form>
        </Card>
    </AppLayout>
</template>
