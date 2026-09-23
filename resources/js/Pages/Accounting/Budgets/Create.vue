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
    incomeAccounts: Array,
    expenseAccounts: Array,
});

const form = useForm({
    name: '',
    fiscal_year: new Date().getFullYear(),
    notes: '',
    amounts: Object.fromEntries([...props.incomeAccounts, ...props.expenseAccounts].map((a) => [a.id, '0'])),
});

function submit() {
    form.transform((data) => ({
        ...data,
        lines: Object.entries(data.amounts).map(([account_id, amount]) => ({ account_id: Number(account_id), amount })),
    })).post(route('accounting.budgets.store'));
}
</script>

<template>
    <Head title="New Budget" />

    <AppLayout :breadcrumbs="[{ label: 'Accounting' }, { label: 'Budgets', href: route('accounting.budgets.index') }, { label: 'New' }]">
        <template #header>
            <PageHeader title="New Budget" />
        </template>

        <form class="max-w-3xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel for="name" value="Budget Name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" placeholder="e.g. FY2026 Operating Budget" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="fiscal_year" value="Fiscal Year" />
                    <TextInput id="fiscal_year" v-model="form.fiscal_year" type="number" step="1" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.fiscal_year" class="mt-2" />
                </div>
            </div>

            <h3 class="mb-2 mt-6 text-xs font-medium uppercase text-gray-400">Income (annual)</h3>
            <div class="space-y-2">
                <div v-for="account in incomeAccounts" :key="account.id" class="flex items-center gap-3">
                    <label class="flex-1 text-sm text-gray-700">{{ account.code }} — {{ account.name }}</label>
                    <TextInput v-model="form.amounts[account.id]" type="number" step="0.0001" class="w-40 text-right" />
                </div>
            </div>

            <h3 class="mb-2 mt-6 text-xs font-medium uppercase text-gray-400">Expenses (annual)</h3>
            <div class="space-y-2">
                <div v-for="account in expenseAccounts" :key="account.id" class="flex items-center gap-3">
                    <label class="flex-1 text-sm text-gray-700">{{ account.code }} — {{ account.name }}</label>
                    <TextInput v-model="form.amounts[account.id]" type="number" step="0.0001" class="w-40 text-right" />
                </div>
            </div>
            <InputError :message="form.errors.lines" class="mt-2" />

            <div class="mt-6">
                <InputLabel for="notes" value="Notes (optional)" />
                <textarea
                    id="notes"
                    v-model="form.notes"
                    rows="2"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('accounting.budgets.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Budget</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
