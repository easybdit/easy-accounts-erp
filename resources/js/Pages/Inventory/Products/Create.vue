<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ProductForm from './Partials/ProductForm.vue';

const props = defineProps({
    categories: Array,
    incomeAccounts: Array,
    expenseAccounts: Array,
    assetAccounts: Array,
});

const form = useForm({
    sku: '',
    name: '',
    product_category_id: '',
    type: 'inventory',
    unit: 'pcs',
    purchase_price: 0,
    selling_price: '',
    income_account_id: '',
    cogs_account_id: '',
    inventory_account_id: '',
    low_stock_threshold: '',
    is_active: true,
    opening_quantity: '',
    opening_date: new Date().toISOString().slice(0, 10),
});

function submit() {
    form.post(route('inventory.products.store'));
}
</script>

<template>
    <Head title="New Product" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Inventory' },
            { label: 'Products', href: route('inventory.products.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Product" />
        </template>

        <form class="max-w-3xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <ProductForm
                :form="form"
                :categories="categories"
                :income-accounts="incomeAccounts"
                :expense-accounts="expenseAccounts"
                :asset-accounts="assetAccounts"
                :is-create="true"
            />

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('inventory.products.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Product</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
