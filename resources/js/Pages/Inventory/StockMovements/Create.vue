<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    products: Array,
});

const form = useForm({
    product_id: '',
    counted_quantity: '',
    date: new Date().toISOString().slice(0, 10),
    reference: '',
    notes: '',
});

const selectedProduct = computed(() => props.products.find((p) => p.id === form.product_id));

function submit() {
    form.post(route('inventory.stock-movements.store'));
}
</script>

<template>
    <Head title="Adjust Stock" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Inventory' },
            { label: 'Stock Movements', href: route('inventory.stock-movements.index') },
            { label: 'Adjust' },
        ]"
    >
        <template #header>
            <PageHeader title="Adjust Stock" />
        </template>

        <form class="max-w-xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-6">
                <div>
                    <InputLabel for="product_id" value="Product" />
                    <select
                        id="product_id"
                        v-model="form.product_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="" disabled>Select a product</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">
                            {{ product.sku }} — {{ product.name }} (on hand: {{ product.current_stock }})
                        </option>
                    </select>
                    <InputError :message="form.errors.product_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="counted_quantity" value="Counted Quantity" />
                    <TextInput id="counted_quantity" v-model="form.counted_quantity" type="number" step="0.0001" class="mt-1 block w-full" required />
                    <p v-if="selectedProduct" class="mt-1 text-xs text-gray-400">
                        Current system quantity: {{ selectedProduct.current_stock }}
                    </p>
                    <InputError :message="form.errors.counted_quantity" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="date" value="Date" />
                    <TextInput id="date" v-model="form.date" type="date" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.date" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="reference" value="Reference (optional)" />
                    <TextInput id="reference" v-model="form.reference" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.reference" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="notes" value="Notes (optional)" />
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="2"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>

                <InputError :message="form.errors.adjustment" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('inventory.stock-movements.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :loading="form.processing">Save Adjustment</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
