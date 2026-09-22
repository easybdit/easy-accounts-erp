<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

defineProps({
    assetAccounts: Array,
    expenseAccounts: Array,
});

const form = useForm({
    name: '',
    asset_account_id: '',
    accumulated_depreciation_account_id: '',
    depreciation_expense_account_id: '',
    purchase_date: new Date().toISOString().slice(0, 10),
    purchase_cost: '',
    salvage_value: 0,
    useful_life_months: '',
    notes: '',
});

function submit() {
    form.post(route('accounting.fixed-assets.store'));
}
</script>

<template>
    <Head title="New Fixed Asset" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Accounting' },
            { label: 'Fixed Assets', href: route('accounting.fixed-assets.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Fixed Asset" />
        </template>

        <form class="max-w-3xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="mb-6">
                <InputLabel for="name" value="Asset Name" />
                <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" placeholder="e.g. Dell PowerEdge Server" required />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel for="asset_account_id" value="Asset Account" />
                    <select
                        id="asset_account_id"
                        v-model="form.asset_account_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="" disabled>Select an account</option>
                        <option v-for="account in assetAccounts" :key="account.id" :value="account.id">
                            {{ account.code }} — {{ account.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.asset_account_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="accumulated_depreciation_account_id" value="Accumulated Depreciation Account" />
                    <select
                        id="accumulated_depreciation_account_id"
                        v-model="form.accumulated_depreciation_account_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="" disabled>Select an account</option>
                        <option v-for="account in assetAccounts" :key="account.id" :value="account.id">
                            {{ account.code }} — {{ account.name }}
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-gray-400">A contra-asset account (e.g. "Accumulated Depreciation"). Must differ from the asset account above.</p>
                    <InputError :message="form.errors.accumulated_depreciation_account_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="depreciation_expense_account_id" value="Depreciation Expense Account" />
                    <select
                        id="depreciation_expense_account_id"
                        v-model="form.depreciation_expense_account_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="" disabled>Select an account</option>
                        <option v-for="account in expenseAccounts" :key="account.id" :value="account.id">
                            {{ account.code }} — {{ account.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.depreciation_expense_account_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="purchase_date" value="Purchase Date" />
                    <TextInput id="purchase_date" v-model="form.purchase_date" type="date" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.purchase_date" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="purchase_cost" value="Purchase Cost" />
                    <TextInput id="purchase_cost" v-model="form.purchase_cost" type="number" step="0.0001" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.purchase_cost" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="salvage_value" value="Salvage Value (optional)" />
                    <TextInput id="salvage_value" v-model="form.salvage_value" type="number" step="0.0001" class="mt-1 block w-full" />
                    <p class="mt-1 text-xs text-gray-400">The estimated residual value at the end of its useful life. Defaults to 0.</p>
                    <InputError :message="form.errors.salvage_value" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="useful_life_months" value="Useful Life (months)" />
                    <TextInput id="useful_life_months" v-model="form.useful_life_months" type="number" step="1" min="1" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.useful_life_months" class="mt-2" />
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
                <Link :href="route('accounting.fixed-assets.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Asset</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
