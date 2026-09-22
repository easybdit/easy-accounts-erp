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
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    invoice: Object,
    amountPaid: String,
    amountDue: String,
    activePaymentLink: Object,
    depositAccounts: Array,
});

const confirmingPost = ref(false);
const confirmingDelete = ref(false);
const confirmingPaymentLink = ref(false);
const confirmingEmail = ref(false);
const depositAccountId = ref('');
const linkCopied = ref(false);

const emailForm = useForm({
    recipient_email: props.invoice.customer.email ?? '',
});

function sendEmail() {
    emailForm.post(route('sales.invoices.email', props.invoice.id), {
        onFinish: () => (confirmingEmail.value = false),
    });
}

function post() {
    router.post(route('sales.invoices.post', props.invoice.id), {}, {
        onFinish: () => (confirmingPost.value = false),
    });
}

function destroy() {
    router.delete(route('sales.invoices.destroy', props.invoice.id));
}

function generatePaymentLink() {
    router.post(route('sales.invoices.payment-link', props.invoice.id), {
        deposit_account_id: depositAccountId.value,
    }, {
        onFinish: () => (confirmingPaymentLink.value = false),
    });
}

function copyLink() {
    navigator.clipboard.writeText(props.activePaymentLink.url);
    linkCopied.value = true;
    setTimeout(() => (linkCopied.value = false), 2000);
}
</script>

<template>
    <Head :title="invoice.invoice_number" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Sales' },
            { label: 'Invoices', href: route('sales.invoices.index') },
            { label: invoice.invoice_number },
        ]"
    >
        <template #header>
            <PageHeader :title="invoice.invoice_number">
                <template #actions>
                    <a :href="route('sales.invoices.pdf', invoice.id)">
                        <SecondaryButton type="button">Download PDF</SecondaryButton>
                    </a>
                    <template v-if="invoice.status === 'draft'">
                        <Link :href="route('sales.invoices.edit', invoice.id)">
                            <SecondaryButton type="button">Edit</SecondaryButton>
                        </Link>
                        <DangerButton type="button" @click="confirmingDelete = true">Delete</DangerButton>
                        <PrimaryButton type="button" @click="confirmingPost = true">Post Invoice</PrimaryButton>
                    </template>
                    <template v-else-if="parseFloat(amountDue) > 0">
                        <Link :href="route('sales.payments.create')">
                            <PrimaryButton type="button">Receive Payment</PrimaryButton>
                        </Link>
                        <SecondaryButton type="button" @click="confirmingPaymentLink = true">
                            {{ activePaymentLink ? 'New Payment Link' : 'Generate Payment Link' }}
                        </SecondaryButton>
                    </template>
                    <SecondaryButton v-if="invoice.status === 'posted'" type="button" @click="confirmingEmail = true">
                        Email Invoice
                    </SecondaryButton>
                </template>
            </PageHeader>
        </template>

        <div v-if="activePaymentLink" class="mb-4 flex items-center justify-between rounded-md bg-indigo-50 px-4 py-3 text-sm text-indigo-700">
            <span class="truncate">Payment link: {{ activePaymentLink.url }}</span>
            <button type="button" class="ml-3 shrink-0 font-medium underline" @click="copyLink">
                {{ linkCopied ? 'Copied!' : 'Copy' }}
            </button>
        </div>

        <Card padded>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Customer</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('customers.show', invoice.customer.id)" class="text-indigo-600 hover:text-indigo-900">
                            {{ invoice.customer.name }}
                        </Link>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Invoice Date</dt>
                    <dd class="text-sm text-gray-800">{{ invoice.invoice_date }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Due Date</dt>
                    <dd class="text-sm text-gray-800">{{ invoice.due_date ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Status</dt>
                    <dd>
                        <Badge :variant="invoice.status === 'posted' ? 'success' : 'warning'" class="capitalize">
                            {{ invoice.status }}
                        </Badge>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Receivable Account</dt>
                    <dd class="text-sm text-gray-800">{{ invoice.receivable_account.code }} — {{ invoice.receivable_account.name }}</dd>
                </div>
                <div v-if="invoice.status === 'posted'">
                    <dt class="text-xs font-medium uppercase text-gray-400">Paid / Due</dt>
                    <dd class="text-sm text-gray-800">
                        {{ amountPaid }} paid —
                        <span :class="parseFloat(amountDue) > 0 ? 'font-semibold text-red-600' : 'font-semibold text-green-600'">
                            {{ amountDue }} due
                        </span>
                    </dd>
                </div>
                <div v-if="invoice.journal">
                    <dt class="text-xs font-medium uppercase text-gray-400">Posted Journal</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('accounting.journals.show', invoice.journal.id)" class="text-indigo-600 hover:text-indigo-900">
                            #{{ invoice.journal.id }}
                        </Link>
                    </dd>
                </div>
                <div v-if="invoice.last_emailed_at">
                    <dt class="text-xs font-medium uppercase text-gray-400">Last Emailed</dt>
                    <dd class="text-sm text-gray-800">{{ new Date(invoice.last_emailed_at).toLocaleString() }}</dd>
                </div>
                <div v-if="invoice.notes" class="sm:col-span-4">
                    <dt class="text-xs font-medium uppercase text-gray-400">Notes</dt>
                    <dd class="text-sm text-gray-800">{{ invoice.notes }}</dd>
                </div>
            </dl>

            <table class="mt-6 min-w-full divide-y divide-gray-200">
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
                    <tr v-for="item in invoice.items" :key="item.id">
                        <td class="px-2 py-2 text-sm text-gray-700">
                            <template v-if="item.is_deferred && invoice.status === 'posted'">
                                {{ item.deferred_revenue_account.code }} — {{ item.deferred_revenue_account.name }}
                            </template>
                            <template v-else>{{ item.account.code }} — {{ item.account.name }}</template>
                        </td>
                        <td class="px-2 py-2 text-sm text-gray-500">
                            {{ item.description }}
                            <Link
                                v-if="item.is_deferred && item.revenue_recognition_schedule"
                                :href="route('sales.revenue-recognition.show', item.revenue_recognition_schedule.id)"
                                class="ml-1 text-xs text-indigo-600 hover:text-indigo-900"
                            >
                                (Deferred over {{ item.deferred_months }} months — view schedule)
                            </Link>
                            <span v-else-if="item.is_deferred" class="ml-1 text-xs text-amber-600">
                                (Will defer over {{ item.deferred_months }} months once posted)
                            </span>
                        </td>
                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ item.quantity }}</td>
                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ item.unit_price }}</td>
                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ item.discount }}</td>
                        <td class="px-2 py-2 text-sm text-gray-500">
                            {{ item.tax_rate ? `${item.tax_rate.name} (${item.tax_amount})` : '—' }}
                        </td>
                        <td class="px-2 py-2 text-right text-sm font-medium text-gray-800">{{ item.line_total }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-4 flex justify-end">
                <dl class="w-64 space-y-1 text-sm">
                    <div class="flex justify-between">
                        <dt>Subtotal</dt>
                        <dd>{{ invoice.subtotal }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Discount</dt>
                        <dd>{{ invoice.discount_total }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Tax</dt>
                        <dd>{{ invoice.tax_total }}</dd>
                    </div>
                    <div class="flex justify-between text-base font-semibold">
                        <dt>Total</dt>
                        <dd>{{ invoice.total }}</dd>
                    </div>
                </dl>
            </div>

            <div v-if="invoice.payment_allocations?.length" class="mt-6 border-t border-gray-100 pt-4">
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
                        <tr v-for="allocation in invoice.payment_allocations" :key="allocation.id">
                            <td class="px-2 py-2 text-sm text-gray-700">
                                <Link :href="route('sales.payments.show', allocation.payment.id)" class="text-indigo-600 hover:text-indigo-900">
                                    {{ allocation.payment.payment_number }}
                                </Link>
                            </td>
                            <td class="px-2 py-2 text-sm text-gray-500">{{ allocation.payment.payment_date }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ allocation.amount }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <Modal :show="confirmingPost" @close="confirmingPost = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Post this invoice?</h2>
                <p class="mt-1 text-sm text-gray-500">
                    This creates the accounting journal entry and locks the invoice from further edits.
                    This cannot be undone from here.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingPost = false">Cancel</SecondaryButton>
                    <PrimaryButton @click="post">Post</PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="confirmingDelete" @close="confirmingDelete = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Delete this draft invoice?</h2>
                <p class="mt-1 text-sm text-gray-500">This action cannot be undone.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingDelete = false">Cancel</SecondaryButton>
                    <DangerButton @click="destroy">Delete</DangerButton>
                </div>
            </div>
        </Modal>

        <Modal :show="confirmingPaymentLink" @close="confirmingPaymentLink = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Generate a payment link?</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Share this link with the customer so they can pay online via SSLCommerz. Generating a new
                    link deactivates any earlier one for this invoice.
                </p>
                <div class="mt-4">
                    <InputLabel for="deposit_account_id" value="Deposit To" />
                    <select
                        id="deposit_account_id"
                        v-model="depositAccountId"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="" disabled>Select an account</option>
                        <option v-for="account in depositAccounts" :key="account.id" :value="account.id">
                            {{ account.code }} — {{ account.name }}
                        </option>
                    </select>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingPaymentLink = false">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="!depositAccountId" @click="generatePaymentLink">Generate</PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="confirmingEmail" @close="confirmingEmail = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Email this invoice?</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Sends a PDF copy of this invoice{{ activePaymentLink ? ', with a link to pay online,' : '' }}
                    to the address below.
                </p>
                <div class="mt-4">
                    <InputLabel for="recipient_email" value="Recipient Email" />
                    <TextInput
                        id="recipient_email"
                        v-model="emailForm.recipient_email"
                        type="email"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError :message="emailForm.errors.recipient_email" class="mt-2" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingEmail = false">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="!emailForm.recipient_email" @click="sendEmail">Send</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
