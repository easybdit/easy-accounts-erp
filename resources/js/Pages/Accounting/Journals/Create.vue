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
    accounts: Array,
    customers: Array,
    vendors: Array,
});

const form = useForm({
    date: new Date().toISOString().slice(0, 10),
    reference: '',
    description: '',
    lines: [
        { account_id: '', party: '', debit: '', credit: '', description: '' },
        { account_id: '', party: '', debit: '', credit: '', description: '' },
    ],
});

function addLine() {
    form.lines.push({ account_id: '', party: '', debit: '', credit: '', description: '' });
}

function submitTransform(lines) {
    return lines.map((line) => {
        const [partyType, partyId] = line.party ? line.party.split(':') : [null, null];

        return {
            account_id: line.account_id,
            debit: line.debit,
            credit: line.credit,
            description: line.description,
            customer_id: partyType === 'customer' ? partyId : null,
            vendor_id: partyType === 'vendor' ? partyId : null,
        };
    });
}

function removeLine(index) {
    if (form.lines.length > 2) {
        form.lines.splice(index, 1);
    }
}

const totalDebit = computed(() =>
    form.lines.reduce((sum, line) => sum + (parseFloat(line.debit) || 0), 0)
);
const totalCredit = computed(() =>
    form.lines.reduce((sum, line) => sum + (parseFloat(line.credit) || 0), 0)
);
const isBalanced = computed(
    () => totalDebit.value > 0 && Math.abs(totalDebit.value - totalCredit.value) < 0.0001
);

function submit() {
    form.transform((data) => ({
        ...data,
        lines: submitTransform(data.lines),
    })).post(route('accounting.journals.store'));
}
</script>

<template>
    <Head title="New Journal Entry" />

    <AppLayout
        :breadcrumbs="[
            { label: 'Accounting' },
            { label: 'Journal', href: route('accounting.journals.index') },
            { label: 'New' },
        ]"
    >
        <template #header>
            <PageHeader title="New Journal Entry" />
        </template>

        <form class="rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
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
                <div class="sm:col-span-1">
                    <InputLabel for="description" value="Description (optional)" />
                    <TextInput id="description" v-model="form.description" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Account</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Customer/Vendor</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Debit</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Credit</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Line Description</th>
                            <th class="px-2 py-2" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(line, index) in form.lines" :key="index">
                            <td class="px-2 py-2">
                                <select
                                    v-model="line.account_id"
                                    class="block w-56 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="" disabled>Select account</option>
                                    <option v-for="account in accounts" :key="account.id" :value="account.id">
                                        {{ account.code }} — {{ account.name }}
                                    </option>
                                </select>
                            </td>
                            <td class="px-2 py-2">
                                <select
                                    v-model="line.party"
                                    class="block w-48 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">None</option>
                                    <optgroup label="Customers">
                                        <option v-for="c in customers" :key="`customer-${c.id}`" :value="`customer:${c.id}`">
                                            {{ c.name }}
                                        </option>
                                    </optgroup>
                                    <optgroup label="Vendors">
                                        <option v-for="v in vendors" :key="`vendor-${v.id}`" :value="`vendor:${v.id}`">
                                            {{ v.name }}
                                        </option>
                                    </optgroup>
                                </select>
                            </td>
                            <td class="px-2 py-2">
                                <input
                                    v-model="line.debit"
                                    type="number"
                                    step="0.0001"
                                    class="block w-32 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </td>
                            <td class="px-2 py-2">
                                <input
                                    v-model="line.credit"
                                    type="number"
                                    step="0.0001"
                                    class="block w-32 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </td>
                            <td class="px-2 py-2">
                                <input
                                    v-model="line.description"
                                    type="text"
                                    class="block w-48 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </td>
                            <td class="px-2 py-2 text-right">
                                <button
                                    type="button"
                                    class="text-sm text-red-600 hover:text-red-800"
                                    :disabled="form.lines.length <= 2"
                                    @click="removeLine(index)"
                                >
                                    Remove
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <InputError :message="form.errors.lines" class="mt-2" />

                <button
                    type="button"
                    class="mt-3 text-sm font-medium text-indigo-600 hover:text-indigo-800"
                    @click="addLine"
                >
                    + Add line
                </button>
            </div>

            <div class="mt-6 flex items-center justify-end gap-6 border-t border-gray-100 pt-4 text-sm">
                <span>Total Debit: <strong>{{ totalDebit.toFixed(4) }}</strong></span>
                <span>Total Credit: <strong>{{ totalCredit.toFixed(4) }}</strong></span>
                <span :class="isBalanced ? 'text-green-600' : 'text-red-600'" class="font-medium">
                    {{ isBalanced ? 'Balanced' : 'Not Balanced' }}
                </span>
            </div>

            <p class="mt-2 text-xs text-gray-400">
                This total is a UI convenience only — the backend independently recalculates and
                enforces debit = credit before posting (Section 45).
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="route('accounting.journals.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">Post Journal</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
