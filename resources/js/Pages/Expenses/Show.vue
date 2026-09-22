<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    expense: Object,
    totalPaid: String,
});

const attachmentPendingDelete = ref(null);

function destroyAttachment() {
    router.delete(route('expenses.entries.attachments.destroy', [props.expense.id, attachmentPendingDelete.value]), {
        onFinish: () => (attachmentPendingDelete.value = null),
    });
}
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
                <div v-if="expense.tax_rate">
                    <dt class="text-xs font-medium uppercase text-gray-400">Tax</dt>
                    <dd class="text-sm text-gray-800">{{ expense.tax_rate.name }} ({{ expense.tax_amount }})</dd>
                </div>
                <div v-if="expense.tax_rate">
                    <dt class="text-xs font-medium uppercase text-gray-400">Total Paid</dt>
                    <dd class="text-sm font-semibold text-gray-800">{{ totalPaid }}</dd>
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

            <div v-if="expense.attachments.length > 0" class="mt-6 border-t border-gray-100 pt-4">
                <h3 class="text-xs font-medium uppercase text-gray-400">Receipts / Attachments</h3>
                <ul class="mt-2 divide-y divide-gray-100">
                    <li v-for="attachment in expense.attachments" :key="attachment.id" class="flex items-center justify-between py-2">
                        <a
                            :href="route('expenses.entries.attachments.download', [expense.id, attachment.id])"
                            class="text-sm text-indigo-600 hover:text-indigo-900"
                        >
                            {{ attachment.original_filename }}
                        </a>
                        <button
                            type="button"
                            class="text-sm text-red-600 hover:text-red-800"
                            @click="attachmentPendingDelete = attachment.id"
                        >
                            Delete
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <Modal :show="attachmentPendingDelete !== null" @close="attachmentPendingDelete = null">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Delete this attachment?</h2>
                <p class="mt-1 text-sm text-gray-500">This action cannot be undone.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="attachmentPendingDelete = null">Cancel</SecondaryButton>
                    <DangerButton @click="destroyAttachment">Delete</DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
