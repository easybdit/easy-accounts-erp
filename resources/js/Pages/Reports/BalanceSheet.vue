<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ExportCsvButton from '@/Components/ExportCsvButton.vue';

const props = defineProps({
    assets: Array,
    liabilities: Array,
    equity: Array,
    currentEarnings: String,
    totalAssets: String,
    totalLiabilities: String,
    totalEquity: String,
    isBalanced: Boolean,
    asOf: String,
});

const asOf = ref(props.asOf);

function apply() {
    router.get(route('reports.balance-sheet'), { as_of: asOf.value || undefined }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Balance Sheet" />

    <AppLayout :breadcrumbs="[{ label: 'Reports', href: route('reports.index') }, { label: 'Balance Sheet' }]">
        <template #header>
            <PageHeader title="Balance Sheet">
                <template #actions>
                    <ExportCsvButton route-name="reports.balance-sheet" :params="{ as_of: asOf }" />
                </template>
            </PageHeader>
        </template>

        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
            <div>
                <label class="block text-xs font-medium text-gray-500">As of</label>
                <input v-model="asOf" type="date" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="apply" />
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-medium" :class="isBalanced ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                {{ isBalanced ? 'Balanced' : 'Not Balanced' }}
            </span>
        </div>

        <div class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-2 text-sm font-semibold text-gray-700">Assets</h2>
            <div class="mb-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="row in assets" :key="row.id">
                            <td class="py-2 text-sm text-gray-700">{{ row.code }} — {{ row.name }}</td>
                            <td class="py-2 text-right text-sm text-gray-700">{{ row.balance }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="font-medium">
                            <td class="py-2 text-sm text-gray-700">Total Assets</td>
                            <td class="py-2 text-right text-sm text-gray-900">{{ totalAssets }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <h2 class="mb-2 text-sm font-semibold text-gray-700">Liabilities</h2>
            <div class="mb-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="row in liabilities" :key="row.id">
                            <td class="py-2 text-sm text-gray-700">{{ row.code }} — {{ row.name }}</td>
                            <td class="py-2 text-right text-sm text-gray-700">{{ row.balance }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="font-medium">
                            <td class="py-2 text-sm text-gray-700">Total Liabilities</td>
                            <td class="py-2 text-right text-sm text-gray-900">{{ totalLiabilities }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <h2 class="mb-2 text-sm font-semibold text-gray-700">Equity</h2>
            <div class="mb-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="row in equity" :key="row.id">
                            <td class="py-2 text-sm text-gray-700">{{ row.code }} — {{ row.name }}</td>
                            <td class="py-2 text-right text-sm text-gray-700">{{ row.balance }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-sm italic text-gray-500">Current Period Earnings</td>
                            <td class="py-2 text-right text-sm italic text-gray-500">{{ currentEarnings }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="font-medium">
                            <td class="py-2 text-sm text-gray-700">Total Equity</td>
                            <td class="py-2 text-right text-sm text-gray-900">{{ totalEquity }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="flex justify-between border-t border-gray-200 pt-4 text-base font-semibold">
                <span>Total Liabilities + Equity</span>
                <span>{{ (parseFloat(totalLiabilities) + parseFloat(totalEquity)).toFixed(4) }}</span>
            </div>
        </div>
    </AppLayout>
</template>
