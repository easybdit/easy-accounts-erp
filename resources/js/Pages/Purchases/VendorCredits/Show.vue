<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
    vendorCredit: Object,
});

const confirmingPost = ref(false);
const confirmingDelete = ref(false);

function post() {
    router.post(route('purchases.vendor-credits.post', props.vendorCredit.id), {}, {
        onFinish: () => (confirmingPost.value = false),
    });
}

function destroy() {
    router.delete(route('purchases.vendor-credits.destroy', props.vendorCredit.id));
}
</script>

<template>
    <Head :title="vendorCredit.vendor_credit_number" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Purchases' },
            { label: 'Vendor Credits', href: route('purchases.vendor-credits.index') },
            { label: vendorCredit.vendor_credit_number },
        ]"
    >
        <template #header>
            <PageHeader :title="vendorCredit.vendor_credit_number">
                <template #actions>
                    <template v-if="vendorCredit.status === 'draft'">
                        <Link :href="route('purchases.vendor-credits.edit', vendorCredit.id)">
                            <SecondaryButton type="button">Edit</SecondaryButton>
                        </Link>
                        <DangerButton type="button" @click="confirmingDelete = true">Delete</DangerButton>
                        <PrimaryButton type="button" @click="confirmingPost = true">Post Vendor Credit</PrimaryButton>
                    </template>
                </template>
            </PageHeader>
        </template>

        <Card padded>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Vendor</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('vendors.show', vendorCredit.vendor.id)" class="text-indigo-600 hover:text-indigo-900">
                            {{ vendorCredit.vendor.name }}
                        </Link>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Date</dt>
                    <dd class="text-sm text-gray-800">{{ vendorCredit.vendor_credit_date }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Related Bill</dt>
                    <dd class="text-sm text-gray-800">
                        <Link
                            v-if="vendorCredit.bill"
                            :href="route('purchases.bills.show', vendorCredit.bill.id)"
                            class="text-indigo-600 hover:text-indigo-900"
                        >
                            {{ vendorCredit.bill.bill_number }}
                        </Link>
                        <span v-else>—</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Status</dt>
                    <dd>
                        <Badge :variant="vendorCredit.status === 'posted' ? 'success' : 'warning'" class="capitalize">
                            {{ vendorCredit.status }}
                        </Badge>
                    </dd>
                </div>
                <div v-if="vendorCredit.journal">
                    <dt class="text-xs font-medium uppercase text-gray-400">Posted Journal</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('accounting.journals.show', vendorCredit.journal.id)" class="text-indigo-600 hover:text-indigo-900">
                            #{{ vendorCredit.journal.id }}
                        </Link>
                    </dd>
                </div>
                <div v-if="vendorCredit.notes" class="sm:col-span-4">
                    <dt class="text-xs font-medium uppercase text-gray-400">Notes</dt>
                    <dd class="text-sm text-gray-800">{{ vendorCredit.notes }}</dd>
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
                        <tr v-for="item in vendorCredit.items" :key="item.id">
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
            </div>

            <div class="mt-4 flex justify-end">
                <dl class="w-64 space-y-1 text-sm">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ vendorCredit.subtotal }}</dd></div>
                    <div class="flex justify-between"><dt>Discount</dt><dd>{{ vendorCredit.discount_total }}</dd></div>
                    <div class="flex justify-between"><dt>Tax</dt><dd>{{ vendorCredit.tax_total }}</dd></div>
                    <div class="flex justify-between text-base font-semibold"><dt>Total</dt><dd>{{ vendorCredit.total }}</dd></div>
                </dl>
            </div>
        </Card>

        <Modal :show="confirmingPost" @close="confirmingPost = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Post this vendor credit?</h2>
                <p class="mt-1 text-sm text-gray-500">
                    This reduces the vendor's balance and reverses the related expense. It cannot be edited afterward.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingPost = false">Cancel</SecondaryButton>
                    <PrimaryButton @click="post">Post</PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="confirmingDelete" @close="confirmingDelete = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Delete this vendor credit?</h2>
                <p class="mt-1 text-sm text-gray-500">This action cannot be undone.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingDelete = false">Cancel</SecondaryButton>
                    <DangerButton @click="destroy">Delete</DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
