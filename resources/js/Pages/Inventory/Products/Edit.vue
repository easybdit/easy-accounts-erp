<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ProductForm from './Partials/ProductForm.vue';

const props = defineProps({
    product: Object,
    categories: Array,
    incomeAccounts: Array,
    expenseAccounts: Array,
    assetAccounts: Array,
});

const form = useForm({
    sku: props.product.sku,
    name: props.product.name,
    product_category_id: props.product.product_category_id,
    type: props.product.type,
    unit: props.product.unit,
    purchase_price: props.product.purchase_price,
    selling_price: props.product.selling_price,
    income_account_id: props.product.income_account_id,
    cogs_account_id: props.product.cogs_account_id,
    inventory_account_id: props.product.inventory_account_id,
    low_stock_threshold: props.product.low_stock_threshold,
    is_active: props.product.is_active,
});

function submit() {
    form.put(route('inventory.products.update', props.product.id));
}
</script>

<template>
    <Head title="Edit Product" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Inventory' },
            { label: 'Products', href: route('inventory.products.index') },
            { label: product.name },
        ]"
    >
        <template #header>
            <PageHeader :title="`Edit Product — ${product.name}`" />
        </template>

        <form class="max-w-3xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <ProductForm
                :form="form"
                :categories="categories"
                :income-accounts="incomeAccounts"
                :expense-accounts="expenseAccounts"
                :asset-accounts="assetAccounts"
                :is-create="false"
            />

            <p class="mt-4 text-xs text-gray-400">
                Note: quantity cannot be changed here. Use a Stock Adjustment to correct it.
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('inventory.products.show', product.id)">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
