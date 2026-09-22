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
    asset: Object,
    assetAccounts: Array,
    expenseAccounts: Array,
});

const locked = computed(() => props.asset.months_depreciated > 0);

const form = useForm({
    name: props.asset.name,
    asset_account_id: props.asset.asset_account_id,
    accumulated_depreciation_account_id: props.asset.accumulated_depreciation_account_id,
    depreciation_expense_account_id: props.asset.depreciation_expense_account_id,
    purchase_date: props.asset.purchase_date,
    purchase_cost: props.asset.purchase_cost,
    salvage_value: props.asset.salvage_value,
    useful_life_months: props.asset.useful_life_months,
    notes: props.asset.notes ?? '',
});

function submit() {
    form.put(route('accounting.fixed-assets.update', props.asset.id));
}
</script>

<template>
    <Head title="Edit Fixed Asset" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Accounting' },
            { label: 'Fixed Assets', href: route('accounting.fixed-assets.index') },
            { label: asset.name },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit — ${asset.name}`" />
        </template>

        <div v-if="locked" class="mb-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-700">
            Depreciation has already been posted for this asset — cost, salvage value, useful life, and the asset
            account can no longer be changed.
        </div>

        <form class="max-w-3xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="mb-6">
                <InputLabel for="name" value="Asset Name" />
                <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel for="asset_account_id" value="Asset Account" />
                    <select
                        id="asset_account_id"
                        v-model="form.asset_account_id"
                        :disabled="locked"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
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
                    <TextInput id="purchase_cost" v-model="form.purchase_cost" type="number" step="0.0001" :disabled="locked" class="mt-1 block w-full disabled:bg-gray-100" required />
                    <InputError :message="form.errors.purchase_cost" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="salvage_value" value="Salvage Value (optional)" />
                    <TextInput id="salvage_value" v-model="form.salvage_value" type="number" step="0.0001" :disabled="locked" class="mt-1 block w-full disabled:bg-gray-100" />
                    <InputError :message="form.errors.salvage_value" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="useful_life_months" value="Useful Life (months)" />
                    <TextInput id="useful_life_months" v-model="form.useful_life_months" type="number" step="1" min="1" :disabled="locked" class="mt-1 block w-full disabled:bg-gray-100" required />
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
                <Link :href="route('accounting.fixed-assets.show', asset.id)">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
