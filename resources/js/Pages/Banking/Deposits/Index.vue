<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    undepositedPayments: Array,
    deposits: Object,
    bankAccounts: Array,
    hasUndepositedFundsAccount: Boolean,
});

const selected = ref([]);

const form = useForm({
    bank_account_id: '',
    deposit_date: new Date().toISOString().slice(0, 10),
    reference: '',
    notes: '',
    payment_ids: [],
});

const selectedTotal = computed(() =>
    props.undepositedPayments
        .filter((payment) => selected.value.includes(payment.id))
        .reduce((sum, payment) => sum + Number(payment.amount), 0)
        .toFixed(4)
);

function toggleAll(event) {
    selected.value = event.target.checked ? props.undepositedPayments.map((p) => p.id) : [];
}

function submit() {
    form.payment_ids = selected.value;
    form.post(route('banking.deposits.store'), {
        onSuccess: () => (selected.value = []),
    });
}
</script>

<template>
    <Head title="Bank Deposits" />

    <AppLayout :breadcrumbs="[{ label: 'Banking' }, { label: 'Deposits' }]">
        <template #header>
            <PageHeader title="Bank Deposits" />
        </template>

        <div v-if="!hasUndepositedFundsAccount" class="mb-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
            No account is flagged as "Undeposited Funds" in the Chart of Accounts, so no payments can be batched
            here yet.
        </div>

        <Card padded class="mb-6">
            <h2 class="mb-3 text-sm font-semibold text-gray-700">Payments Awaiting Deposit</h2>

            <div v-if="undepositedPayments.length" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-2 py-2">
                                <input type="checkbox" class="rounded border-gray-300" @change="toggleAll" />
                            </th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Payment #</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Customer</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Method</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="payment in undepositedPayments" :key="payment.id">
                            <td class="px-2 py-2">
                                <input v-model="selected" type="checkbox" :value="payment.id" class="rounded border-gray-300" />
                            </td>
                            <td class="px-2 py-2 text-sm text-gray-700">{{ payment.payment_number }}</td>
                            <td class="px-2 py-2 text-sm text-gray-700">{{ payment.customer.name }}</td>
                            <td class="px-2 py-2 text-sm text-gray-500">{{ payment.payment_date }}</td>
                            <td class="px-2 py-2 text-sm text-gray-500">{{ payment.method ?? '—' }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ payment.amount }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState
                v-else
                title="Nothing to deposit"
                description="Payments received into Undeposited Funds will show up here, ready to batch into a deposit."
            />

            <form v-if="undepositedPayments.length" class="mt-6 border-t border-gray-100 pt-4" @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div>
                        <InputLabel for="bank_account_id" value="Deposit To" />
                        <select
                            id="bank_account_id"
                            v-model="form.bank_account_id"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option value="" disabled>Select a bank account</option>
                            <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                                {{ account.code }} — {{ account.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.bank_account_id" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="deposit_date" value="Deposit Date" />
                        <TextInput id="deposit_date" v-model="form.deposit_date" type="date" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.deposit_date" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="reference" value="Reference (optional)" />
                        <TextInput id="reference" v-model="form.reference" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.reference" class="mt-2" />
                    </div>
                    <div class="flex items-end justify-between">
                        <span class="text-sm text-gray-600">Selected total: <strong>{{ selectedTotal }}</strong></span>
                    </div>
                </div>
                <InputError :message="form.errors.payment_ids" class="mt-2" />
                <div class="mt-4 flex justify-end">
                    <PrimaryButton :disabled="selected.length === 0" :loading="form.processing">
                        Deposit {{ selected.length }} Payment(s)
                    </PrimaryButton>
                </div>
            </form>
        </Card>

        <Card>
            <h2 class="px-4 pt-4 text-sm font-semibold text-gray-700">Recent Deposits</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Deposit #</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Bank Account</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="deposit in deposits.data" :key="deposit.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <Link :href="route('banking.deposits.show', deposit.id)" class="text-indigo-600 hover:text-indigo-900">
                                    {{ deposit.deposit_number }}
                                </Link>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                {{ deposit.bank_account.code }} — {{ deposit.bank_account.name }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ deposit.deposit_date }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ deposit.amount }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-if="deposits.data.length === 0" title="No deposits yet" />
        </Card>
    </AppLayout>
</template>
