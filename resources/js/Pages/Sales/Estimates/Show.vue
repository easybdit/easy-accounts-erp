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
    estimate: Object,
});

const confirmingConvert = ref(false);
const confirmingDelete = ref(false);
const confirmingEmail = ref(false);

const statusVariant = {
    draft: 'neutral',
    sent: 'info',
    accepted: 'success',
    declined: 'danger',
    converted: 'success',
};

const emailForm = useForm({
    recipient_email: props.estimate.customer.email ?? '',
});

function convert() {
    router.post(route('sales.estimates.convert', props.estimate.id), {}, {
        onFinish: () => (confirmingConvert.value = false),
    });
}

function destroy() {
    router.delete(route('sales.estimates.destroy', props.estimate.id));
}

function sendEmail() {
    emailForm.post(route('sales.estimates.email', props.estimate.id), {
        onFinish: () => (confirmingEmail.value = false),
    });
}
</script>

<template>
    <Head :title="estimate.estimate_number" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Sales' },
            { label: 'Estimates', href: route('sales.estimates.index') },
            { label: estimate.estimate_number },
        ]"
    >
        <template #header>
            <PageHeader :title="estimate.estimate_number">
                <template #actions>
                    <a :href="route('sales.estimates.pdf', estimate.id)">
                        <SecondaryButton type="button">Download PDF</SecondaryButton>
                    </a>
                    <template v-if="estimate.status !== 'converted'">
                        <SecondaryButton type="button" @click="confirmingEmail = true">Email Estimate</SecondaryButton>
                        <Link :href="route('sales.estimates.edit', estimate.id)">
                            <SecondaryButton type="button">Edit</SecondaryButton>
                        </Link>
                        <DangerButton type="button" @click="confirmingDelete = true">Delete</DangerButton>
                        <PrimaryButton type="button" @click="confirmingConvert = true">Convert to Invoice</PrimaryButton>
                    </template>
                </template>
            </PageHeader>
        </template>

        <div v-if="estimate.converted_invoice" class="mb-4 rounded-md bg-indigo-50 px-4 py-3 text-sm text-indigo-700">
            This estimate was converted to invoice
            <Link :href="route('sales.invoices.show', estimate.converted_invoice.id)" class="font-medium underline">
                {{ estimate.converted_invoice.invoice_number }}
            </Link>.
        </div>

        <Card padded>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Customer</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('customers.show', estimate.customer.id)" class="text-indigo-600 hover:text-indigo-900">
                            {{ estimate.customer.name }}
                        </Link>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Estimate Date</dt>
                    <dd class="text-sm text-gray-800">{{ estimate.estimate_date }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Expiry Date</dt>
                    <dd class="text-sm text-gray-800">{{ estimate.expiry_date ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Status</dt>
                    <dd><Badge :variant="statusVariant[estimate.status]" class="capitalize">{{ estimate.status }}</Badge></dd>
                </div>
                <div v-if="estimate.last_emailed_at">
                    <dt class="text-xs font-medium uppercase text-gray-400">Last Emailed</dt>
                    <dd class="text-sm text-gray-800">{{ new Date(estimate.last_emailed_at).toLocaleString() }}</dd>
                </div>
                <div v-if="estimate.notes" class="sm:col-span-4">
                    <dt class="text-xs font-medium uppercase text-gray-400">Notes</dt>
                    <dd class="text-sm text-gray-800">{{ estimate.notes }}</dd>
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
                    <tr v-for="item in estimate.items" :key="item.id">
                        <td class="px-2 py-2 text-sm text-gray-700">{{ item.account.code }} — {{ item.account.name }}</td>
                        <td class="px-2 py-2 text-sm text-gray-500">{{ item.description }}</td>
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
                    <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ estimate.subtotal }}</dd></div>
                    <div class="flex justify-between"><dt>Discount</dt><dd>{{ estimate.discount_total }}</dd></div>
                    <div class="flex justify-between"><dt>Tax</dt><dd>{{ estimate.tax_total }}</dd></div>
                    <div class="flex justify-between text-base font-semibold"><dt>Total</dt><dd>{{ estimate.total }}</dd></div>
                </dl>
            </div>
        </Card>

        <Modal :show="confirmingConvert" @close="confirmingConvert = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Convert this estimate to an invoice?</h2>
                <p class="mt-1 text-sm text-gray-500">
                    This creates a new draft invoice with today's date from this estimate's items. The estimate itself will be
                    locked once converted.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingConvert = false">Cancel</SecondaryButton>
                    <PrimaryButton @click="convert">Convert</PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="confirmingDelete" @close="confirmingDelete = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Delete this estimate?</h2>
                <p class="mt-1 text-sm text-gray-500">This action cannot be undone.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingDelete = false">Cancel</SecondaryButton>
                    <DangerButton @click="destroy">Delete</DangerButton>
                </div>
            </div>
        </Modal>

        <Modal :show="confirmingEmail" @close="confirmingEmail = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Email this estimate?</h2>
                <p class="mt-1 text-sm text-gray-500">Sends a PDF copy of this estimate to the address below.</p>
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
