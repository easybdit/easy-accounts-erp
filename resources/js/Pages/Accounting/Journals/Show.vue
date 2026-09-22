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
    journal: Object,
    canBeVoided: Boolean,
});

const confirmingVoid = ref(false);

function voidJournal() {
    router.post(route('accounting.journals.void', props.journal.id), {}, {
        onFinish: () => (confirmingVoid.value = false),
    });
}
</script>

<template>
    <Head :title="`Journal #${journal.id}`" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Accounting' },
            { label: 'Journal', href: route('accounting.journals.index') },
            { label: `#${journal.id}` },
        ]"
    >
        <template #header>
            <PageHeader :title="`Journal #${journal.id}`">
                <template #actions>
                    <DangerButton v-if="canBeVoided" type="button" @click="confirmingVoid = true">Void Journal</DangerButton>
                </template>
            </PageHeader>
        </template>

        <div v-if="journal.voided_at" class="mb-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-700">
            This journal was voided on {{ journal.voided_at }}.
            <Link
                v-if="journal.reversal_journal"
                :href="route('accounting.journals.show', journal.reversal_journal.id)"
                class="font-medium underline"
            >
                View the reversing journal
            </Link>
        </div>
        <div v-if="journal.reversal_of_journal" class="mb-4 rounded-md bg-indigo-50 px-4 py-3 text-sm text-indigo-700">
            This is a reversal of
            <Link :href="route('accounting.journals.show', journal.reversal_of_journal.id)" class="font-medium underline">
                Journal #{{ journal.reversal_of_journal.id }}
            </Link>.
        </div>

        <Card padded>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Date</dt>
                    <dd class="text-sm text-gray-800">{{ journal.date }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Reference</dt>
                    <dd class="text-sm text-gray-800">{{ journal.reference ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Status</dt>
                    <dd>
                        <Badge v-if="journal.voided_at" variant="warning">Voided</Badge>
                        <Badge v-else-if="journal.reversal_of_journal" variant="info">Reversal</Badge>
                        <Badge v-else variant="success">Posted</Badge>
                    </dd>
                </div>
                <div class="sm:col-span-3">
                    <dt class="text-xs font-medium uppercase text-gray-400">Description</dt>
                    <dd class="text-sm text-gray-800">{{ journal.description ?? '—' }}</dd>
                </div>
            </dl>

            <table class="mt-6 min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Account</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
                        <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Debit</th>
                        <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Credit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="entry in journal.entries" :key="entry.id">
                        <td class="px-2 py-2 text-sm text-gray-700">{{ entry.account.code }} — {{ entry.account.name }}</td>
                        <td class="px-2 py-2 text-sm text-gray-500">{{ entry.description ?? '—' }}</td>
                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ entry.debit }}</td>
                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ entry.credit }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-4 flex justify-end">
                <Link :href="route('accounting.journals.index')" class="text-sm text-indigo-600 hover:text-indigo-900">
                    Back to Journal
                </Link>
            </div>
        </Card>

        <Modal :show="confirmingVoid" @close="confirmingVoid = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Void this journal?</h2>
                <p class="mt-1 text-sm text-gray-500">
                    This posts a new reversing journal that exactly cancels this one out. The original entries stay in the
                    ledger for audit purposes — nothing is edited or deleted.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingVoid = false">Cancel</SecondaryButton>
                    <DangerButton @click="voidJournal">Void Journal</DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
