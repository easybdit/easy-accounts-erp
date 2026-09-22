<script setup>
import { computed, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    accounts: {
        type: Array,
        default: () => [],
    },
    types: {
        type: Array,
        default: () => [],
    },
});

const parentOptions = computed(() =>
    props.accounts.filter((account) => !props.form.type || account.type === props.form.type)
);

// Convenience default, same spirit as the backend migration's backfill:
// equity is financing activity, everything else starts as operating. The
// user can still freely override per account (e.g. a fixed-asset or loan
// account) before saving.
watch(
    () => props.form.type,
    (type) => {
        if (!props.form.cash_flow_category || props.form.cash_flow_category === 'operating') {
            props.form.cash_flow_category = type === 'equity' ? 'financing' : 'operating';
        }
    }
);
</script>

<template>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <InputLabel for="code" value="Account Code" />
            <TextInput id="code" v-model="form.code" type="text" class="mt-1 block w-full" required />
            <InputError :message="form.errors.code" class="mt-2" />
        </div>

        <div>
            <InputLabel for="name" value="Account Name" />
            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
            <InputError :message="form.errors.name" class="mt-2" />
        </div>

        <div>
            <InputLabel for="type" value="Account Type" />
            <select
                id="type"
                v-model="form.type"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                <option value="" disabled>Select a type</option>
                <option v-for="type in types" :key="type" :value="type">
                    {{ type.charAt(0).toUpperCase() + type.slice(1) }}
                </option>
            </select>
            <InputError :message="form.errors.type" class="mt-2" />
        </div>

        <div>
            <InputLabel for="parent_id" value="Parent Account (optional)" />
            <select
                id="parent_id"
                v-model="form.parent_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option :value="null">No parent</option>
                <option v-for="account in parentOptions" :key="account.id" :value="account.id">
                    {{ account.code }} — {{ account.name }}
                </option>
            </select>
            <InputError :message="form.errors.parent_id" class="mt-2" />
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

        <div v-if="!form.is_bank_account">
            <InputLabel for="cash_flow_category" value="Cash Flow Category" />
            <select
                id="cash_flow_category"
                v-model="form.cash_flow_category"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                <option value="operating">Operating</option>
                <option value="investing">Investing</option>
                <option value="financing">Financing</option>
            </select>
            <p class="mt-1 text-xs text-gray-400">Used to classify this account's movements on the Cash Flow Statement.</p>
            <InputError :message="form.errors.cash_flow_category" class="mt-2" />
        </div>

        <div class="flex items-center gap-2 pt-6">
            <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded border-gray-300" />
            <InputLabel for="is_active" value="Active" />
        </div>

        <div v-if="form.type === 'asset'" class="flex items-center gap-2 pt-6">
            <input id="is_bank_account" v-model="form.is_bank_account" type="checkbox" class="rounded border-gray-300" />
            <InputLabel for="is_bank_account" value="Bank/Cash Account (shows on the Banking overview)" />
        </div>
    </div>
</template>
