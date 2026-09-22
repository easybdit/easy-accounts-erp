<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    product: Object,
    currentStock: String,
    stockValue: String,
    isLowStock: Boolean,
    movements: Array,
});
</script>

<template>
    <Head :title="product.name" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Inventory' },
            { label: 'Products', href: route('inventory.products.index') },
            { label: product.name },
        ]"
    >
        <template #header>
            <PageHeader :title="product.name">
                <template #actions>
                    <Link v-if="product.type === 'inventory'" :href="route('inventory.stock-movements.create')">
                        <PrimaryButton type="button">Adjust Stock</PrimaryButton>
                    </Link>
                    <Link :href="route('inventory.products.edit', product.id)">
                        <SecondaryButton type="button">Edit</SecondaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-400">SKU</p>
                <p class="text-sm text-gray-800">{{ product.sku }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-400">Category</p>
                <p class="text-sm text-gray-800">{{ product.category.name }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-400">Selling Price</p>
                <p class="text-sm text-gray-800">{{ product.selling_price }}</p>
            </div>
            <div v-if="product.type === 'inventory'" class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-400">Current Stock</p>
                <p class="text-lg font-semibold" :class="isLowStock ? 'text-red-600' : 'text-gray-900'">
                    {{ currentStock }} {{ product.unit }}
                </p>
                <p v-if="isLowStock" class="text-xs font-medium text-red-600">Low stock</p>
            </div>
            <div v-else class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-400">Type</p>
                <p class="text-sm text-gray-800">Service (no stock tracking)</p>
            </div>
        </div>

        <div v-if="product.type === 'inventory'" class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-400">Purchase Price / Cost</p>
                <p class="text-sm text-gray-800">{{ product.purchase_price }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-400">Stock Value (qty × cost)</p>
                <p class="text-sm text-gray-800">{{ stockValue }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-400">Inventory / COGS Accounts</p>
                <p class="text-sm text-gray-800">
                    {{ product.inventory_account.code }} / {{ product.cogs_account.code }}
                </p>
            </div>
        </div>

        <div v-if="product.type === 'inventory'" class="overflow-hidden rounded-lg bg-white shadow-sm">
            <div class="border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Stock Movements</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reason</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reference</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Quantity</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Balance</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="movement in movements" :key="movement.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ movement.date }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm capitalize text-gray-500">{{ movement.reason }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ movement.reference ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ movement.quantity }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-800">{{ movement.running_balance }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <Link :href="route('inventory.stock-movements.show', movement.id)" class="text-indigo-600 hover:text-indigo-900">
                                View
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="movements.length === 0">
                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">
                            No stock movements yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
