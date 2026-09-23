<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
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
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    asset: Object,
    accumulatedDepreciation: String,
    bookValue: String,
    assetAccounts: Array,
    gainLossAccounts: Array,
});

const confirmingDispose = ref(false);

const statusVariant = {
    active: 'success',
    fully_depreciated: 'info',
    disposed: 'neutral',
};

const disposeForm = useForm({
    disposal_proceeds: '0',
    disposal_proceeds_account_id: '',
    gain_loss_account_id: '',
    disposal_notes: '',
});

const projectedGainLoss = computed(() => {
    const proceeds = Number(disposeForm.disposal_proceeds || 0);
    const book = Number(props.bookValue || 0);
    return proceeds - book;
});

const gainLossRequired = computed(() => Math.abs(projectedGainLoss.value) >= 0.0001);
const proceedsAccountRequired = computed(() => Number(disposeForm.disposal_proceeds || 0) > 0);

function postDepreciation() {
    router.post(route('accounting.fixed-assets.post-depreciation', props.asset.id));
}

function dispose() {
    disposeForm.post(route('accounting.fixed-assets.dispose', props.asset.id), {
        onFinish: () => (confirmingDispose.value = false),
    });
}
</script>

<template>
    <Head :title="asset.name" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Accounting' },
            { label: 'Fixed Assets', href: route('accounting.fixed-assets.index') },
            { label: asset.name },
        ]"
    >
        <template #header>
            <PageHeader :title="asset.name">
                <template #actions>
                    <template v-if="asset.status === 'active'">
                        <Link :href="route('accounting.fixed-assets.edit', asset.id)">
                            <SecondaryButton type="button">Edit</SecondaryButton>
                        </Link>
                        <PrimaryButton type="button" @click="postDepreciation">Post This Month's Depreciation</PrimaryButton>
                    </template>
                    <DangerButton v-if="asset.status !== 'disposed'" type="button" @click="confirmingDispose = true">
                        Dispose
                    </DangerButton>
                </template>
            </PageHeader>
        </template>

        <Card padded>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Purchase Date</dt>
                    <dd class="text-sm text-gray-800">{{ asset.purchase_date }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Status</dt>
                    <dd><Badge :variant="statusVariant[asset.status]" class="capitalize">{{ asset.status.replace('_', ' ') }}</Badge></dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Useful Life</dt>
                    <dd class="text-sm text-gray-800">{{ asset.useful_life_months }} months ({{ asset.months_depreciated }} depreciated)</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Asset Account</dt>
                    <dd class="text-sm text-gray-800">{{ asset.asset_account.code }} — {{ asset.asset_account.name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Purchase Cost</dt>
                    <dd class="text-sm text-gray-800">{{ asset.purchase_cost }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Salvage Value</dt>
                    <dd class="text-sm text-gray-800">{{ asset.salvage_value }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Accumulated Depreciation</dt>
                    <dd class="text-sm text-gray-800">{{ accumulatedDepreciation }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Book Value</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ bookValue }}</dd>
                </div>
                <div v-if="asset.disposed_at" class="sm:col-span-4">
                    <dt class="text-xs font-medium uppercase text-gray-400">Disposed</dt>
                    <dd class="text-sm text-gray-800">
                        {{ asset.disposed_at }}
                        <span v-if="asset.disposal_notes">— {{ asset.disposal_notes }}</span>
                    </dd>
                </div>
                <div v-if="asset.notes" class="sm:col-span-4">
                    <dt class="text-xs font-medium uppercase text-gray-400">Notes</dt>
                    <dd class="text-sm text-gray-800">{{ asset.notes }}</dd>
                </div>
            </dl>

            <h3 class="mt-6 text-xs font-medium uppercase text-gray-400">Depreciation History</h3>
            <div class="mt-2 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Period</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="entry in asset.depreciations" :key="entry.id">
                            <td class="px-2 py-2 text-sm text-gray-700">{{ entry.period_date }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ entry.amount }}</td>
                        </tr>
                        <tr v-if="asset.depreciations.length === 0">
                            <td colspan="2" class="px-2 py-4 text-center text-sm text-gray-400">No depreciation posted yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <Modal :show="confirmingDispose" @close="confirmingDispose = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Dispose this asset?</h2>
                <p class="mt-1 text-sm text-gray-500">
                    No further depreciation will be posted for this asset. This does not reverse depreciation
                    already posted. Book value at disposal is <strong>{{ bookValue }}</strong>.
                </p>

                <div class="mt-4">
                    <InputLabel for="disposal_proceeds" value="Disposal Proceeds (optional)" />
                    <TextInput
                        id="disposal_proceeds"
                        v-model="disposeForm.disposal_proceeds"
                        type="number"
                        step="0.0001"
                        min="0"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="disposeForm.errors.disposal_proceeds" class="mt-2" />
                </div>

                <div v-if="proceedsAccountRequired" class="mt-4">
                    <InputLabel for="disposal_proceeds_account_id" value="Proceeds Account" />
                    <select
                        id="disposal_proceeds_account_id"
                        v-model="disposeForm.disposal_proceeds_account_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="" disabled>Select an account</option>
                        <option v-for="account in assetAccounts" :key="account.id" :value="account.id">
                            {{ account.code }} — {{ account.name }}
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-gray-400">Where the proceeds were deposited (e.g. Cash or Bank).</p>
                    <InputError :message="disposeForm.errors.disposal_proceeds_account_id" class="mt-2" />
                </div>

                <div v-if="gainLossRequired" class="mt-4">
                    <InputLabel for="gain_loss_account_id" :value="projectedGainLoss >= 0 ? 'Gain on Disposal Account' : 'Loss on Disposal Account'" />
                    <select
                        id="gain_loss_account_id"
                        v-model="disposeForm.gain_loss_account_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="" disabled>Select an account</option>
                        <option v-for="account in gainLossAccounts" :key="account.id" :value="account.id">
                            {{ account.code }} — {{ account.name }}
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-gray-400">
                        Projected {{ projectedGainLoss >= 0 ? 'gain' : 'loss' }} of {{ Math.abs(projectedGainLoss).toFixed(4) }}
                        (proceeds less book value).
                    </p>
                    <InputError :message="disposeForm.errors.gain_loss_account_id" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="disposal_notes" value="Notes (optional)" />
                    <textarea
                        id="disposal_notes"
                        v-model="disposeForm.disposal_notes"
                        rows="2"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError :message="disposeForm.errors.disposal_notes" class="mt-2" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingDispose = false">Cancel</SecondaryButton>
                    <DangerButton @click="dispose">Dispose</DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
