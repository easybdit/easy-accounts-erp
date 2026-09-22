<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    movements: Object,
    filters: Object,
    products: Array,
});

const page = usePage();
const productId = ref(props.filters.product_id ?? '');

watch(productId, (value) => {
    router.get(
        route('inventory.stock-movements.index'),
        { product_id: value || undefined },
        { preserveState: true, replace: true }
    );
});
</script>

<template>
    <Head title="Stock Movements" />

    <AppLayout :breadcrumbs="[{ label: 'Inventory' }, { label: 'Stock Movements' }]">
        <template #header>
            <PageHeader title="Stock Movements">
                <template #actions>
                    <Link :href="route('inventory.stock-movements.create')">
                        <PrimaryButton>Adjust Stock</PrimaryButton>
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

        <div class="mb-4">
            <select
                v-model="productId"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs"
            >
                <option value="">All products</option>
                <option v-for="product in products" :key="product.id" :value="product.id">
                    {{ product.sku }} — {{ product.name }}
                </option>
            </select>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reason</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Quantity</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="movement in movements.data" :key="movement.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ movement.date }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ movement.product.sku }} — {{ movement.product.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm capitalize text-gray-500">{{ movement.reason }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ movement.quantity }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <Link :href="route('inventory.stock-movements.show', movement.id)" class="text-indigo-600 hover:text-indigo-900">
                                View
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="movements.data.length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                            No stock movements found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="movements.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, index) in movements.links"
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
