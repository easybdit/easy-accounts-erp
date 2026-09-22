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
    from_account_id: '',
    to_account_id: '',
    transfer_date: new Date().toISOString().slice(0, 10),
    amount: '',
    reference: '',
    notes: '',
});

function submit() {
    form.post(route('banking.transfers.store'));
}
</script>

<template>
    <Head title="New Transfer" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Banking' },
            { label: 'Transfers', href: route('banking.transfers.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Transfer" />
        </template>

        <form class="max-w-2xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel for="from_account_id" value="From Account" />
                    <select
                        id="from_account_id"
                        v-model="form.from_account_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="" disabled>Select an account</option>
                        <option v-for="account in accounts" :key="account.id" :value="account.id">
                            {{ account.code }} — {{ account.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.from_account_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="to_account_id" value="To Account" />
                    <select
                        id="to_account_id"
                        v-model="form.to_account_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="" disabled>Select an account</option>
                        <option v-for="account in accounts" :key="account.id" :value="account.id">
                            {{ account.code }} — {{ account.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.to_account_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="amount" value="Amount" />
                    <TextInput id="amount" v-model="form.amount" type="number" step="0.0001" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.amount" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="transfer_date" value="Date" />
                    <TextInput id="transfer_date" v-model="form.transfer_date" type="date" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.transfer_date" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="reference" value="Reference (optional)" />
                    <TextInput id="reference" v-model="form.reference" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.reference" class="mt-2" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="notes" value="Notes (optional)" />
                <textarea
                    id="notes"
                    v-model="form.notes"
                    rows="2"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('banking.transfers.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Record Transfer</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
