<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    expense: Object,
});
</script>

<template>
    <Head :title="expense.expense_number" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Expenses' },
            { label: expense.expense_number },
        ]"
    >
        <template #header>
            <PageHeader :title="expense.expense_number" />
        </template>

        <div class="rounded-lg bg-white p-6 shadow-sm">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Payee</dt>
                    <dd class="text-sm text-gray-800">
                        <Link v-if="expense.vendor" :href="route('vendors.show', expense.vendor.id)" class="text-indigo-600 hover:text-indigo-900">
                            {{ expense.payee }}
                        </Link>
                        <span v-else>{{ expense.payee }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Category</dt>
                    <dd class="text-sm text-gray-800">{{ expense.category.name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Date</dt>
                    <dd class="text-sm text-gray-800">{{ expense.expense_date }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Amount</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ expense.amount }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Expense Account</dt>
                    <dd class="text-sm text-gray-800">{{ expense.account.code }} — {{ expense.account.name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Paid From</dt>
                    <dd class="text-sm text-gray-800">{{ expense.payment_account.code }} — {{ expense.payment_account.name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Reference</dt>
                    <dd class="text-sm text-gray-800">{{ expense.reference ?? '—' }}</dd>
                </div>
                <div v-if="expense.journal">
                    <dt class="text-xs font-medium uppercase text-gray-400">Posted Journal</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('accounting.journals.show', expense.journal.id)" class="text-indigo-600 hover:text-indigo-900">
                            #{{ expense.journal.id }}
                        </Link>
                    </dd>
                </div>
                <div v-if="expense.notes" class="sm:col-span-4">
                    <dt class="text-xs font-medium uppercase text-gray-400">Notes</dt>
                    <dd class="text-sm text-gray-800">{{ expense.notes }}</dd>
                </div>
            </dl>
        </div>
    </AppLayout>
</template>
