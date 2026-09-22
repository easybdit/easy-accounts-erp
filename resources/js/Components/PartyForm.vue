<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

defineProps({
    form: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <InputLabel for="name" value="Name" />
            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
            <InputError :message="form.errors.name" class="mt-2" />
        </div>

        <div>
            <InputLabel for="email" value="Email (optional)" />
            <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" />
            <InputError :message="form.errors.email" class="mt-2" />
        </div>

        <div>
            <InputLabel for="phone" value="Phone (optional)" />
            <TextInput id="phone" v-model="form.phone" type="text" class="mt-1 block w-full" />
            <InputError :message="form.errors.phone" class="mt-2" />
        </div>

        <div>
            <InputLabel for="opening_balance" value="Opening Balance" />
            <TextInput
                id="opening_balance"
                v-model="form.opening_balance"
                type="number"
                step="0.0001"
                class="mt-1 block w-full"
            />
            <InputError :message="form.errors.opening_balance" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
            <InputLabel for="address" value="Address (optional)" />
            <textarea
                id="address"
                v-model="form.address"
                rows="2"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <InputError :message="form.errors.address" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
            <InputLabel for="billing_address" value="Billing Address (optional, if different)" />
            <textarea
                id="billing_address"
                v-model="form.billing_address"
                rows="2"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <InputError :message="form.errors.billing_address" class="mt-2" />
        </div>

        <div class="flex items-center gap-2">
            <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded border-gray-300" />
            <InputLabel for="is_active" value="Active" />
        </div>
    </div>
</template>
