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
    fixedKeys: Array,
});

const form = useForm({
    name: '',
    type: 'earning',
    account_id: '',
    source_component_key: '',
    is_active: true,
});

function submit() {
    form.post(route('hr.payroll-components.store'));
}
</script>

<template>
    <Head title="New Payroll Component" />

    <AppLayout
        :breadcrumbs="[
            { label: 'HR' },
            { label: 'Payroll Components', href: route('hr.payroll-components.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Payroll Component" />
        </template>

        <form class="max-w-xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" type="text" placeholder="e.g. House Rent Allowance" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="type" value="Type" />
                    <select
                        id="type"
                        v-model="form.type"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="earning">Earning (debited to an expense account)</option>
                        <option value="deduction">Deduction (credited to a liability/expense account)</option>
                    </select>
                    <InputError :message="form.errors.type" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="source_component_key" value="Salary Slip Key" />
                    <TextInput
                        id="source_component_key"
                        v-model="form.source_component_key"
                        type="text"
                        list="fixed-keys"
                        placeholder="e.g. basic_salary, net_salary, or an allowance name like house_rent"
                        class="mt-1 block w-full"
                        required
                    />
                    <datalist id="fixed-keys">
                        <option v-for="key in fixedKeys" :key="key" :value="key" />
                    </datalist>
                    <p class="mt-1 text-xs text-gray-500">
                        Use one of {{ fixedKeys.join(', ') }}, or match a key inside an employee's allowances.
                    </p>
                    <InputError :message="form.errors.source_component_key" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="account_id" value="Account" />
                    <select
                        id="account_id"
                        v-model="form.account_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="" disabled>Select an account</option>
                        <option v-for="account in accounts" :key="account.id" :value="account.id">
                            {{ account.code }} — {{ account.name }} ({{ account.type }})
                        </option>
                    </select>
                    <InputError :message="form.errors.account_id" class="mt-2" />
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded border-gray-300" />
                    <InputLabel for="is_active" value="Active" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('hr.payroll-components.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Component</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
