<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ExportCsvButton from '@/Components/ExportCsvButton.vue';

const props = defineProps({
    rows: Array,
    totalCollected: String,
    totalPaid: String,
    totalNet: String,
    from: String,
    to: String,
});

const from = ref(props.from ?? '');
const to = ref(props.to ?? '');

function apply() {
    router.get(
        route('tax.report'),
        { from: from.value || undefined, to: to.value || undefined },
        { preserveState: true, replace: true }
    );
}
</script>

<template>
    <Head title="Tax Report" />

    <AppLayout :breadcrumbs="[{ label: 'Tax' }, { label: 'Report' }]">
        <template #header>
            <PageHeader title="Tax Report">
                <template #actions>
                    <ExportCsvButton route-name="tax.report" :params="{ from, to }" />
                </template>
            </PageHeader>
        </template>

        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
            <div>
                <label class="block text-xs font-medium text-gray-500">From</label>
                <input
                    v-model="from"
                    type="date"
                    class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    @change="apply"
                />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">To</label>
                <input
                    v-model="to"
                    type="date"
                    class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    @change="apply"
                />
            </div>
            <p class="text-xs text-gray-400">Leave blank for all-time. Based only on posted invoices/bills.</p>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tax Rate</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Rate</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Collected (Sales)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Paid (Purchases)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Net Payable</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="row in rows" :key="row.id">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ row.name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-500">{{ row.rate }}%</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ row.collected }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ row.paid }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-800">{{ row.net }}</td>
                        </tr>
                        <tr v-if="rows.length === 0">
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                                No tax rates configured yet.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-right text-sm font-medium text-gray-700">Total</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ totalCollected }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ totalPaid }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ totalNet }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
