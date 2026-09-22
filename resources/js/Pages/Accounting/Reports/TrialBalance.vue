<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    accounts: Array,
    totalDebit: String,
    totalCredit: String,
    isBalanced: Boolean,
    asOf: String,
});

const asOf = ref(props.asOf);

function apply() {
    router.get(
        route('accounting.trial-balance.index'),
        { as_of: asOf.value || undefined },
        { preserveState: true, replace: true }
    );
}
</script>

<template>
    <Head title="Trial Balance" />

    <AppLayout :breadcrumbs="[{ label: 'Accounting' }, { label: 'Trial Balance' }]">
        <template #header>
            <PageHeader title="Trial Balance" />
        </template>

        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
            <div>
                <label class="block text-xs font-medium text-gray-500">As of</label>
                <input
                    v-model="asOf"
                    type="date"
                    class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    @change="apply"
                />
            </div>
            <span
                class="rounded-full px-3 py-1 text-xs font-medium"
                :class="isBalanced ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
            >
                {{ isBalanced ? 'Balanced' : 'Not Balanced' }}
            </span>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Account</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Debit</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Credit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="account in accounts" :key="account.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ account.code }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ account.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm capitalize text-gray-500">{{ account.type }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ account.debit }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ account.credit }}</td>
                    </tr>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-right text-sm font-medium text-gray-700">Total</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ totalDebit }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ totalCredit }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </AppLayout>
</template>
