<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    accounts: Array,
    ledger: Object,
    filters: Object,
});

const accountId = ref(props.filters.account_id ?? '');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');

function apply() {
    router.get(
        route('accounting.ledger.index'),
        {
            account_id: accountId.value || undefined,
            from: from.value || undefined,
            to: to.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}

watch(accountId, apply);
</script>

<template>
    <Head title="General Ledger" />

    <AppLayout :breadcrumbs="[{ label: 'Accounting' }, { label: 'General Ledger' }]">
        <template #header>
            <PageHeader title="General Ledger" />
        </template>

        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
            <div>
                <label class="block text-xs font-medium text-gray-500">Account</label>
                <select
                    v-model="accountId"
                    class="mt-1 block w-64 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">Select an account</option>
                    <option v-for="account in accounts" :key="account.id" :value="account.id">
                        {{ account.code }} — {{ account.name }}
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">From</label>
                <input
                    v-model="from"
                    type="date"
                    class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    @change="apply"
                />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">To</label>
                <input
                    v-model="to"
                    type="date"
                    class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    @change="apply"
                />
            </div>
        </div>

        <div v-if="!ledger" class="rounded-lg bg-white p-6 text-center text-sm text-gray-500 shadow-sm">
            Select an account to view its ledger.
        </div>

        <div v-else class="overflow-hidden rounded-lg bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">
                    {{ ledger.account.code }} — {{ ledger.account.name }}
                </h2>
                <span class="text-sm text-gray-500">
                    Starting balance: <strong>{{ ledger.starting_balance }}</strong>
                </span>
            </div>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reference</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Debit</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Credit</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="entry in ledger.entries" :key="entry.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ entry.date }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                            <Link :href="route('accounting.journals.show', entry.journal_id)" class="text-indigo-600 hover:text-indigo-900">
                                {{ entry.reference ?? `#${entry.journal_id}` }}
                            </Link>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ entry.description ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ entry.debit }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ entry.credit }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-800">{{ entry.running_balance }}</td>
                    </tr>
                    <tr v-if="ledger.entries.length === 0">
                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">
                            No transactions in this period.
                        </td>
                    </tr>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right text-sm font-medium text-gray-700">Ending Balance</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900">
                            {{ ledger.ending_balance }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </AppLayout>
</template>
