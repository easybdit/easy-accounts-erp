<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
    schedule: Object,
    recognized: String,
    remaining: String,
});

function recognizeNow() {
    router.post(route('sales.revenue-recognition.recognize', props.schedule.id));
}
</script>

<template>
    <Head title="Deferred Revenue Schedule" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Sales' },
            { label: 'Deferred Revenue', href: route('sales.revenue-recognition.index') },
            { label: `#${schedule.id}` },
        ]"
    >
        <template #header>
            <PageHeader :title="`Deferred Revenue — ${schedule.invoice_item.description}`">
                <template #actions>
                    <PrimaryButton v-if="schedule.status === 'active'" type="button" @click="recognizeNow">
                        Recognize This Period Now
                    </PrimaryButton>
                </template>
            </PageHeader>
        </template>

        <Card padded>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Invoice</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('sales.invoices.show', schedule.invoice_item.invoice.id)" class="text-indigo-600 hover:text-indigo-900">
                            {{ schedule.invoice_item.invoice.invoice_number }}
                        </Link>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Customer</dt>
                    <dd class="text-sm text-gray-800">{{ schedule.invoice_item.invoice.customer.name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Status</dt>
                    <dd><Badge :variant="schedule.status === 'completed' ? 'success' : 'info'" class="capitalize">{{ schedule.status }}</Badge></dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Progress</dt>
                    <dd class="text-sm text-gray-800">{{ schedule.months_recognized }} / {{ schedule.months_total }} months</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Deferred Revenue Account</dt>
                    <dd class="text-sm text-gray-800">{{ schedule.deferred_revenue_account.code }} — {{ schedule.deferred_revenue_account.name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Income Account</dt>
                    <dd class="text-sm text-gray-800">{{ schedule.income_account.code }} — {{ schedule.income_account.name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Total Amount</dt>
                    <dd class="text-sm text-gray-800">{{ schedule.total_amount }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Recognized / Remaining</dt>
                    <dd class="text-sm text-gray-800">{{ recognized }} / {{ remaining }}</dd>
                </div>
                <div v-if="schedule.next_period_date">
                    <dt class="text-xs font-medium uppercase text-gray-400">Next Period Due</dt>
                    <dd class="text-sm text-gray-800">{{ schedule.next_period_date }}</dd>
                </div>
            </dl>

            <h3 class="mt-6 text-xs font-medium uppercase text-gray-400">Recognition History</h3>
            <div class="mt-2 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Period</th>
                            <th class="px-2 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="entry in schedule.entries" :key="entry.id">
                            <td class="px-2 py-2 text-sm text-gray-700">{{ entry.period_date }}</td>
                            <td class="px-2 py-2 text-right text-sm text-gray-700">{{ entry.amount }}</td>
                        </tr>
                        <tr v-if="schedule.entries.length === 0">
                            <td colspan="2" class="px-2 py-4 text-center text-sm text-gray-400">No revenue recognized yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </AppLayout>
</template>
