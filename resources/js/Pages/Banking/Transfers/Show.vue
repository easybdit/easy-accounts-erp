<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    transfer: Object,
});
</script>

<template>
    <Head :title="transfer.transfer_number" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Banking' },
            { label: 'Transfers', href: route('banking.transfers.index') },
            { label: transfer.transfer_number },
        ]"
    >
        <template #header>
            <PageHeader :title="transfer.transfer_number" />
        </template>

        <div class="rounded-lg bg-white p-6 shadow-sm">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">From</dt>
                    <dd class="text-sm text-gray-800">{{ transfer.from_account.code }} — {{ transfer.from_account.name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">To</dt>
                    <dd class="text-sm text-gray-800">{{ transfer.to_account.code }} — {{ transfer.to_account.name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Date</dt>
                    <dd class="text-sm text-gray-800">{{ transfer.transfer_date }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Amount</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ transfer.amount }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Reference</dt>
                    <dd class="text-sm text-gray-800">{{ transfer.reference ?? '—' }}</dd>
                </div>
                <div v-if="transfer.journal">
                    <dt class="text-xs font-medium uppercase text-gray-400">Posted Journal</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('accounting.journals.show', transfer.journal.id)" class="text-indigo-600 hover:text-indigo-900">
                            #{{ transfer.journal.id }}
                        </Link>
                    </dd>
                </div>
                <div v-if="transfer.notes" class="sm:col-span-4">
                    <dt class="text-xs font-medium uppercase text-gray-400">Notes</dt>
                    <dd class="text-sm text-gray-800">{{ transfer.notes }}</dd>
                </div>
            </dl>
        </div>
    </AppLayout>
</template>
