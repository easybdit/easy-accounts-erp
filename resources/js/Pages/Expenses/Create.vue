<script setup>
import { watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    categories: Array,
    expenseAccounts: Array,
    paymentAccounts: Array,
    vendors: Array,
});

const form = useForm({
    expense_category_id: '',
    account_id: '',
    payment_account_id: '',
    vendor_id: '',
    payee: '',
    expense_date: new Date().toISOString().slice(0, 10),
    amount: '',
    reference: '',
    notes: '',
});

// Convenience: selecting a category pre-fills its default account, if any.
watch(
    () => form.expense_category_id,
    (categoryId) => {
        const category = props.categories.find((c) => c.id === categoryId);
        if (category?.default_account_id) {
            form.account_id = category.default_account_id;
        }
    }
);

// Convenience: selecting a vendor pre-fills the payee name.
watch(
    () => form.vendor_id,
    (vendorId) => {
        const vendor = props.vendors.find((v) => v.id === vendorId);
        if (vendor) {
            form.payee = vendor.name;
        }
    }
);

function submit() {
    form.post(route('expenses.entries.store'));
}
</script>

<template>
    <Head title="New Expense" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Expenses' },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Expense" />
        </template>

        <form class="max-w-3xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel for="expense_category_id" value="Category" />
                    <select
                        id="expense_category_id"
                        v-model="form.expense_category_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="" disabled>Select a category</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.expense_category_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="account_id" value="Expense Account" />
                    <select
                        id="account_id"
                        v-model="form.account_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="" disabled>Select an account</option>
                        <option v-for="account in expenseAccounts" :key="account.id" :value="account.id">
                            {{ account.code }} — {{ account.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.account_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="vendor_id" value="Vendor (optional)" />
                    <select
                        id="vendor_id"
                        v-model="form.vendor_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">None</option>
                        <option v-for="vendor in vendors" :key="vendor.id" :value="vendor.id">
                            {{ vendor.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.vendor_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="payee" value="Payee" />
                    <TextInput id="payee" v-model="form.payee" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.payee" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="payment_account_id" value="Paid From" />
                    <select
                        id="payment_account_id"
                        v-model="form.payment_account_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="" disabled>Select an account</option>
                        <option v-for="account in paymentAccounts" :key="account.id" :value="account.id">
                            {{ account.code }} — {{ account.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.payment_account_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="amount" value="Amount" />
                    <TextInput id="amount" v-model="form.amount" type="number" step="0.0001" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.amount" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="expense_date" value="Date" />
                    <TextInput id="expense_date" v-model="form.expense_date" type="date" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.expense_date" class="mt-2" />
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
                <Link :href="route('expenses.entries.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">Record Expense</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
