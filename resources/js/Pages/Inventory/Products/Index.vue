<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    products: Object,
    filters: Object,
});

const page = usePage();
const search = ref(props.filters.search ?? '');

watch(search, (value) => {
    router.get(
        route('inventory.products.index'),
        { search: value || undefined },
        { preserveState: true, replace: true }
    );
});
</script>

<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="[{ label: 'Inventory' }, { label: 'Products' }]">
        <template #header>
            <PageHeader title="Products">
                <template #actions>
                    <Link :href="route('inventory.products.create')">
                        <PrimaryButton>New Product</PrimaryButton>
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
        <div
            v-if="$page.props.errors?.product"
            class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            {{ $page.props.errors.product }}
        </div>

        <div class="mb-4">
            <input
                v-model="search"
                type="text"
                placeholder="Search by SKU or name..."
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs"
            />
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">SKU</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Selling Price</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Stock</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="product in products.data" :key="product.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ product.sku }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                            <Link :href="route('inventory.products.show', product.id)" class="text-indigo-600 hover:text-indigo-900">
                                {{ product.name }}
                            </Link>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ product.category }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm capitalize text-gray-500">{{ product.type }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ product.selling_price }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <span v-if="product.current_stock !== null" :class="product.is_low_stock ? 'font-semibold text-red-600' : 'text-gray-700'">
                                {{ product.current_stock }} {{ product.unit }}
                            </span>
                            <span v-else class="text-gray-400">—</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <Link :href="route('inventory.products.edit', product.id)" class="text-indigo-600 hover:text-indigo-900">
                                Edit
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="products.data.length === 0">
                        <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                            No products found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="products.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, index) in products.links"
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
