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
    accounts: Array,
});

const form = useForm({
    name: '',
    rate: '',
    liability_account_id: '',
    is_active: true,
});

function submit() {
    form.post(route('tax.withholding-rates.store'));
}
</script>

<template>
    <Head title="New Withholding Tax Rate" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Tax' },
            { label: 'Withholding Rates', href: route('tax.withholding-rates.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Withholding Tax Rate" />
        </template>

        <form class="max-w-xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" type="text" placeholder="e.g. TDS 10% — Professional Fee" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="rate" value="Rate (%)" />
                    <TextInput id="rate" v-model="form.rate" type="number" step="0.0001" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.rate" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="liability_account_id" value="Withholding Liability Account" />
                    <select
                        id="liability_account_id"
                        v-model="form.liability_account_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="" disabled>Select an account</option>
                        <option v-for="account in accounts" :key="account.id" :value="account.id">
                            {{ account.code }} — {{ account.name }}
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-gray-400">The amount owed to the tax authority (e.g. "TDS/VDS Payable") until it's deposited.</p>
                    <InputError :message="form.errors.liability_account_id" class="mt-2" />
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded border-gray-300" />
                    <InputLabel for="is_active" value="Active" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('tax.withholding-rates.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Rate</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
