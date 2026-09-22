<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    account: Object,
    statementDate: String,
    beginningBalance: String,
    entries: Array,
    history: Array,
});

const statementDate = ref(props.statementDate);

function reloadForDate() {
    router.get(route('banking.reconciliation.index', props.account.id), { statement_date: statementDate.value }, {
        preserveState: true,
        preserveScroll: true,
    });
}

const form = useForm({
    statement_date: props.statementDate,
    statement_balance: '',
    entry_ids: [],
});

const clearedAmount = computed(() =>
    props.entries
        .filter((entry) => form.entry_ids.includes(entry.id))
        .reduce((sum, entry) => sum + parseFloat(entry.debit) - parseFloat(entry.credit), 0)
);

const clearedBalance = computed(() => parseFloat(props.beginningBalance) + clearedAmount.value);

const difference = computed(() => {
    const statementBalance = parseFloat(form.statement_balance) || 0;

    return statementBalance - clearedBalance.value;
});

const isBalanced = computed(() => form.statement_balance !== '' && Math.abs(difference.value) < 0.00005);

function submit() {
    form.statement_date = statementDate.value;
    form.post(route('banking.reconciliation.store', props.account.id));
}
</script>

<template>
    <Head title="Reconcile Account" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Banking' },
            { label: 'Accounts', href: route('banking.accounts.index') },
            { label: `Reconcile — ${account.name}` },
        ]"
    >
        <template #header>
            <PageHeader :title="`Reconcile — ${account.code} ${account.name}`" />
        </template>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <Card>
                    <div class="flex flex-wrap items-end gap-4 border-b border-gray-100 p-4">
                        <div>
                            <InputLabel for="statement_date" value="Statement Date" />
                            <TextInput
                                id="statement_date"
                                v-model="statementDate"
                                type="date"
                                class="mt-1 block"
                                @change="reloadForDate"
                            />
                        </div>
                        <div>
                            <InputLabel for="statement_balance" value="Statement Ending Balance" />
                            <TextInput
                                id="statement_balance"
                                v-model="form.statement_balance"
                                type="number"
                                step="0.0001"
                                class="mt-1 block"
                            />
                            <InputError :message="form.errors.statement_balance" class="mt-1" />
                        </div>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2" />
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
                                <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Debit</th>
                                <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Credit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="entry in entries" :key="entry.id" class="hover:bg-gray-50">
                                <td class="px-3 py-2">
                                    <input v-model="form.entry_ids" type="checkbox" :value="entry.id" class="rounded border-gray-300" />
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-700">{{ entry.date }}</td>
                                <td class="px-3 py-2 text-sm text-gray-500">
                                    {{ entry.reference ?? entry.description ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-right text-sm text-gray-700">{{ entry.debit }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right text-sm text-gray-700">{{ entry.credit }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <EmptyState
                        v-if="entries.length === 0"
                        title="Nothing to reconcile"
                        description="Every transaction up to this date is already reconciled."
                    />
                </Card>

                <Card v-if="history.length" padded class="mt-6">
                    <h2 class="mb-3 text-sm font-semibold text-gray-700">Reconciliation History</h2>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Statement Date</th>
                                <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Statement Balance</th>
                                <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Entries</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="record in history" :key="record.id">
                                <td class="px-2 py-2 text-sm text-gray-700">{{ record.statement_date }}</td>
                                <td class="px-2 py-2 text-right text-sm text-gray-700">{{ record.statement_balance }}</td>
                                <td class="px-2 py-2 text-right text-sm text-gray-500">{{ record.entries_count }}</td>
                            </tr>
                        </tbody>
                    </table>
                </Card>
            </div>

            <div>
                <Card padded class="sticky top-4 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Beginning Balance</span>
                        <span class="font-medium text-gray-800">{{ beginningBalance }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Cleared Amount ({{ form.entry_ids.length }} selected)</span>
                        <span class="font-medium text-gray-800">{{ clearedAmount.toFixed(4) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-3">
                        <span class="text-gray-500">Cleared Balance</span>
                        <span class="font-medium text-gray-800">{{ clearedBalance.toFixed(4) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Statement Balance</span>
                        <span class="font-medium text-gray-800">{{ (parseFloat(form.statement_balance) || 0).toFixed(4) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-3 text-base">
                        <span class="font-semibold">Difference</span>
                        <span class="font-semibold" :class="isBalanced ? 'text-green-600' : 'text-red-600'">
                            {{ difference.toFixed(4) }}
                        </span>
                    </div>

                    <InputError :message="form.errors.entry_ids" class="mt-2" />

                    <PrimaryButton
                        class="w-full justify-center"
                        :disabled="!isBalanced"
                        :loading="form.processing"
                        @click="submit"
                    >
                        Finish Reconciliation
                    </PrimaryButton>
                    <p v-if="!isBalanced" class="text-center text-xs text-gray-400">
                        Select entries and enter the statement balance until the difference is zero.
                    </p>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
