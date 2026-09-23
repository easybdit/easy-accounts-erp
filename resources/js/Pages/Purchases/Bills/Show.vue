<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    bill: Object,
    amountPaid: String,
    amountDue: String,
});

const confirmingPost = ref(false);
const confirmingDelete = ref(false);
const attachmentPendingDelete = ref(null);

const attachmentForm = useForm({
    attachments: [],
});

function post() {
    router.post(route('purchases.bills.post', props.bill.id), {}, {
        onFinish: () => (confirmingPost.value = false),
    });
}

function destroy() {
    router.delete(route('purchases.bills.destroy', props.bill.id));
}

function onAttachmentsChange(event) {
    attachmentForm.attachments = Array.from(event.target.files ?? []);
}

function uploadAttachments() {
    attachmentForm.post(route('purchases.bills.attachments.store', props.bill.id), {
        forceFormData: true,
        onSuccess: () => attachmentForm.reset(),
    });
}

function destroyAttachment() {
    router.delete(route('purchases.bills.attachments.destroy', [props.bill.id, attachmentPendingDelete.value]), {
        onFinish: () => (attachmentPendingDelete.value = null),
    });
}
</script>

<template>
    <Head :title="bill.bill_number" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Purchases' },
            { label: 'Bills', href: route('purchases.bills.index') },
            { label: bill.bill_number },
        ]"
    >
        <template #header>
            <PageHeader :title="bill.bill_number">
                <template #actions>
                    <a :href="route('purchases.bills.pdf', bill.id)">
                        <SecondaryButton type="button">Download PDF</SecondaryButton>
                    </a>
                    <template v-if="bill.status === 'draft'">
                        <Link :href="route('purchases.bills.edit', bill.id)">
                            <SecondaryButton type="button">Edit</SecondaryButton>
                        </Link>
                        <DangerButton type="button" @click="confirmingDelete = true">Delete</DangerButton>
                        <PrimaryButton type="button" @click="confirmingPost = true">Post Bill</PrimaryButton>
                    </template>
                    <Link v-else-if="parseFloat(amountDue) > 0" :href="route('purchases.vendor-payments.create')">
                        <PrimaryButton type="button">Make Payment</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <Card padded>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Vendor</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('vendors.show', bill.vendor.id)" class="text-indigo-600 hover:text-indigo-900">
                            {{ bill.vendor.name }}
                        </Link>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Bill Date</dt>
                    <dd class="text-sm text-gray-800">{{ bill.bill_date }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Due Date</dt>
                    <dd class="text-sm text-gray-800">{{ bill.due_date ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Status</dt>
                    <dd>
                        <Badge :variant="bill.status === 'posted' ? 'success' : 'warning'" class="capitalize">
                            {{ bill.status }}
                        </Badge>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Payable Account</dt>
                    <dd class="text-sm text-gray-800">{{ bill.payable_account.code }} — {{ bill.payable_account.name }}</dd>
                </div>
                <div v-if="bill.status === 'posted'">
                    <dt class="text-xs font-medium uppercase text-gray-400">Paid / Due</dt>
                    <dd class="text-sm text-gray-800">
                        {{ amountPaid }} paid —
                        <span :class="parseFloat(amountDue) > 0 ? 'font-semibold text-red-600' : 'font-semibold text-green-600'">
                            {{ amountDue }} due
                        </span>
                    </dd>
                </div>
                <div v-if="bill.journal">
                    <dt class="text-xs font-medium uppercase text-gray-400">Posted Journal</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('accounting.journals.show', bill.journal.id)" class="text-indigo-600 hover:text-indigo-900">
                            #{{ bill.journal.id }}
                        </Link>
                    </dd>
                </div>
                <div v-if="bill.notes" class="sm:col-span-4">
                    <dt class="text-xs font-medium uppercase text-gray-400">Notes</dt>
                    <dd class="text-sm text-gray-800">{{ bill.notes }}</dd>
                </div>
            </dl>

            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Account</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Qty</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Unit Price</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Discount</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tax</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Line Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in bill.items" :key="item.id">
                            <td class="px-2 py-2 text-sm text-gray-700">{{ item.account.code }} — {{ item.account.name }}</td>
                            <td class="px-2 py-2 text-sm text-gray-500">{{ item.description }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ item.quantity }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ item.unit_price }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ item.discount }}</td>
                            <td class="px-2 py-2 text-sm text-gray-500">
                                <span v-if="item.tax_rate">{{ item.tax_rate.name }} ({{ item.tax_amount }})</span>
                                <span v-else>—</span>
                                <span v-if="item.tax_rate_2" class="block">{{ item.tax_rate_2.name }} ({{ item.tax_amount_2 }})</span>
                            </td>
                            <td class="px-2 py-2 text-right text-sm font-medium text-gray-800">{{ item.line_total }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-end">
                <dl class="w-64 space-y-1 text-sm">
                    <div class="flex justify-between">
                        <dt>Subtotal</dt>
                        <dd>{{ bill.subtotal }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Discount</dt>
                        <dd>{{ bill.discount_total }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Tax</dt>
                        <dd>{{ bill.tax_total }}</dd>
                    </div>
                    <div class="flex justify-between text-base font-semibold">
                        <dt>Total</dt>
                        <dd>{{ bill.total }}</dd>
                    </div>
                </dl>
            </div>

            <div v-if="bill.payment_allocations?.length" class="mt-6 border-t border-gray-100 pt-4">
                <h2 class="mb-2 text-sm font-semibold text-gray-700">Payment History</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Payment #</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Applied Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="allocation in bill.payment_allocations" :key="allocation.id">
                            <td class="px-2 py-2 text-sm text-gray-700">
                                <Link :href="route('purchases.vendor-payments.show', allocation.vendor_payment.id)" class="text-indigo-600 hover:text-indigo-900">
                                    {{ allocation.vendor_payment.payment_number }}
                                </Link>
                            </td>
                            <td class="px-2 py-2 text-sm text-gray-500">{{ allocation.vendor_payment.payment_date }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ allocation.amount }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 border-t border-gray-100 pt-4">
                <h3 class="text-xs font-medium uppercase text-gray-400">Attachments</h3>

                <ul v-if="bill.attachments.length > 0" class="mt-2 divide-y divide-gray-100">
                    <li v-for="attachment in bill.attachments" :key="attachment.id" class="flex items-center justify-between py-2">
                        <a
                            :href="route('purchases.bills.attachments.download', [bill.id, attachment.id])"
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
                <p v-else class="mt-2 text-sm text-gray-400">No attachments yet — add the vendor's invoice copy or other supporting documents.</p>

                <form class="mt-3 flex items-end gap-3" @submit.prevent="uploadAttachments">
                    <div class="flex-1">
                        <InputLabel for="attachments" value="Add Attachment(s)" />
                        <input
                            id="attachments"
                            type="file"
                            multiple
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                            @change="onAttachmentsChange"
                        />
                        <p class="mt-1 text-xs text-gray-400">PDF, JPG or PNG, up to 10MB each, 5 files max.</p>
                        <InputError :message="attachmentForm.errors.attachments" class="mt-2" />
                    </div>
                    <SecondaryButton
                        type="submit"
                        :disabled="attachmentForm.attachments.length === 0 || attachmentForm.processing"
                    >
                        Upload
                    </SecondaryButton>
                </form>
            </div>
        </Card>

        <Modal :show="confirmingPost" @close="confirmingPost = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Post this bill?</h2>
                <p class="mt-1 text-sm text-gray-500">
                    This creates the accounting journal entry and locks the bill from further edits.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingPost = false">Cancel</SecondaryButton>
                    <PrimaryButton @click="post">Post</PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="confirmingDelete" @close="confirmingDelete = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Delete this draft bill?</h2>
                <p class="mt-1 text-sm text-gray-500">This action cannot be undone.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingDelete = false">Cancel</SecondaryButton>
                    <DangerButton @click="destroy">Delete</DangerButton>
                </div>
            </div>
        </Modal>

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
