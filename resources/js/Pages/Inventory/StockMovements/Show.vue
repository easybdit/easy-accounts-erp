<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    movement: Object,
});
</script>

<template>
    <Head :title="`Stock Movement #${movement.id}`" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Inventory' },
            { label: 'Stock Movements', href: route('inventory.stock-movements.index') },
            { label: `#${movement.id}` },
        ]"
    >
        <template #header>
            <PageHeader :title="`Stock Movement #${movement.id}`" />
        </template>

        <div class="rounded-lg bg-white p-6 shadow-sm">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Product</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('inventory.products.show', movement.product.id)" class="text-indigo-600 hover:text-indigo-900">
                            {{ movement.product.sku }} — {{ movement.product.name }}
                        </Link>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Date</dt>
                    <dd class="text-sm text-gray-800">{{ movement.date }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Reason</dt>
                    <dd class="text-sm capitalize text-gray-800">{{ movement.reason }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Quantity</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ movement.quantity }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-400">Reference</dt>
                    <dd class="text-sm text-gray-800">{{ movement.reference ?? '—' }}</dd>
                </div>
                <div v-if="movement.journal">
                    <dt class="text-xs font-medium uppercase text-gray-400">Posted Journal</dt>
                    <dd class="text-sm text-gray-800">
                        <Link :href="route('accounting.journals.show', movement.journal.id)" class="text-indigo-600 hover:text-indigo-900">
                            #{{ movement.journal.id }}
                        </Link>
                    </dd>
                </div>
                <div v-if="movement.notes" class="sm:col-span-3">
                    <dt class="text-xs font-medium uppercase text-gray-400">Notes</dt>
                    <dd class="text-sm text-gray-800">{{ movement.notes }}</dd>
                </div>
            </dl>
        </div>
    </AppLayout>
</template>
