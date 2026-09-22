<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    invoices: Object,
    filters: Object,
});

const page = usePage();
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

function apply() {
    router.get(
        route('sales.invoices.index'),
        { search: search.value || undefined, status: status.value || undefined },
        { preserveState: true, replace: true }
    );
}

watch([search, status], apply);
</script>

<template>
    <Head title="Invoices" />

    <AppLayout :breadcrumbs="[{ label: 'Sales' }, { label: 'Invoices' }]">
        <template #header>
            <PageHeader title="Invoices">
                <template #actions>
                    <Link :href="route('sales.invoices.create')">
                        <PrimaryButton>New Invoice</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div
            v-if="page.props.flash?.success"
            class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700"
        >
            {{ page.props.flash.success }}
        </div>

        <div class="mb-4 flex flex-wrap gap-3">
            <input
                v-model="search"
                type="text"
                placeholder="Search by invoice # or customer..."
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs"
            />
            <select
                v-model="status"
                class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="posted">Posted</option>
            </select>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Invoice #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="invoice in invoices.data" :key="invoice.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                            <Link :href="route('sales.invoices.show', invoice.id)" class="text-indigo-600 hover:text-indigo-900">
                                {{ invoice.invoice_number }}
                            </Link>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ invoice.customer.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ invoice.invoice_date }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ invoice.total }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium"
                                :class="invoice.status === 'posted' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                            >
                                {{ invoice.status }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <Link v-if="invoice.status === 'draft'" :href="route('sales.invoices.edit', invoice.id)" class="text-indigo-600 hover:text-indigo-900">
                                Edit
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="invoices.data.length === 0">
                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">
                            No invoices found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="invoices.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, index) in invoices.links"
                :key="index"
                :href="link.url ?? '#'"
                v-html="link.label"
                class="rounded-md px-3 py-1 text-sm"
                :class="[
                    link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                    !link.url ? 'pointer-events-none opacity-50' : '',
                ]"
            />
        </div>
    </AppLayout>
</template>
