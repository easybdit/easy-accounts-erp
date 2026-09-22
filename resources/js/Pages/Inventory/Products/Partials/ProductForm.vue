<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    incomeAccounts: {
        type: Array,
        default: () => [],
    },
    expenseAccounts: {
        type: Array,
        default: () => [],
    },
    assetAccounts: {
        type: Array,
        default: () => [],
    },
    isCreate: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <InputLabel for="sku" value="SKU" />
            <TextInput id="sku" v-model="form.sku" type="text" class="mt-1 block w-full" required />
            <InputError :message="form.errors.sku" class="mt-2" />
        </div>

        <div>
            <InputLabel for="name" value="Name" />
            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
            <InputError :message="form.errors.name" class="mt-2" />
        </div>

        <div>
            <InputLabel for="product_category_id" value="Category" />
            <select
                id="product_category_id"
                v-model="form.product_category_id"
                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                <option value="" disabled>Select a category</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                </option>
            </select>
            <InputError :message="form.errors.product_category_id" class="mt-2" />
        </div>

        <div>
            <InputLabel for="type" value="Type" />
            <select
                id="type"
                v-model="form.type"
                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                <option value="inventory">Inventory (tracks stock)</option>
                <option value="service">Service (no stock tracking)</option>
            </select>
            <InputError :message="form.errors.type" class="mt-2" />
        </div>

        <div>
            <InputLabel for="unit" value="Unit" />
            <TextInput id="unit" v-model="form.unit" type="text" placeholder="pcs, kg, hour..." class="mt-1 block w-full" required />
            <InputError :message="form.errors.unit" class="mt-2" />
        </div>

        <div>
            <InputLabel for="selling_price" value="Selling Price" />
            <TextInput id="selling_price" v-model="form.selling_price" type="number" step="0.0001" class="mt-1 block w-full" required />
            <InputError :message="form.errors.selling_price" class="mt-2" />
        </div>

        <div>
            <InputLabel for="purchase_price" value="Purchase Price / Cost" />
            <TextInput id="purchase_price" v-model="form.purchase_price" type="number" step="0.0001" class="mt-1 block w-full" />
            <InputError :message="form.errors.purchase_price" class="mt-2" />
        </div>

        <div>
            <InputLabel for="income_account_id" value="Income Account" />
            <select
                id="income_account_id"
                v-model="form.income_account_id"
                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                <option value="" disabled>Select an account</option>
                <option v-for="account in incomeAccounts" :key="account.id" :value="account.id">
                    {{ account.code }} — {{ account.name }}
                </option>
            </select>
            <InputError :message="form.errors.income_account_id" class="mt-2" />
        </div>

        <template v-if="form.type === 'inventory'">
            <div>
                <InputLabel for="inventory_account_id" value="Inventory (Asset) Account" />
                <select
                    id="inventory_account_id"
                    v-model="form.inventory_account_id"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option value="" disabled>Select an account</option>
                    <option v-for="account in assetAccounts" :key="account.id" :value="account.id">
                        {{ account.code }} — {{ account.name }}
                    </option>
                </select>
                <InputError :message="form.errors.inventory_account_id" class="mt-2" />
            </div>

            <div>
                <InputLabel for="cogs_account_id" value="Cost of Goods Sold Account" />
                <select
                    id="cogs_account_id"
                    v-model="form.cogs_account_id"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option value="" disabled>Select an account</option>
                    <option v-for="account in expenseAccounts" :key="account.id" :value="account.id">
                        {{ account.code }} — {{ account.name }}
                    </option>
                </select>
                <InputError :message="form.errors.cogs_account_id" class="mt-2" />
            </div>

            <div>
                <InputLabel for="low_stock_threshold" value="Low Stock Threshold (optional)" />
                <TextInput id="low_stock_threshold" v-model="form.low_stock_threshold" type="number" step="0.0001" class="mt-1 block w-full" />
                <InputError :message="form.errors.low_stock_threshold" class="mt-2" />
            </div>

            <template v-if="isCreate">
                <div>
                    <InputLabel for="opening_quantity" value="Opening Quantity (optional)" />
                    <TextInput id="opening_quantity" v-model="form.opening_quantity" type="number" step="0.0001" class="mt-1 block w-full" />
                    <InputError :message="form.errors.opening_quantity" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="opening_date" value="Opening Date" />
                    <TextInput id="opening_date" v-model="form.opening_date" type="date" class="mt-1 block w-full" />
                    <InputError :message="form.errors.opening_date" class="mt-2" />
                </div>
            </template>
        </template>

        <div class="flex items-center gap-2 pt-6">
            <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded border-gray-300" />
            <InputLabel for="is_active" value="Active" />
        </div>
    </div>
</template>
