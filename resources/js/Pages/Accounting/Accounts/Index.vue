<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    accounts: Object,
    filters: Object,
    types: Array,
});

const page = usePage();

const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? '');
const confirmingDeleteId = ref(null);

watch([search, type], ([searchValue, typeValue]) => {
    router.get(
        route('accounting.accounts.index'),
        { search: searchValue || undefined, type: typeValue || undefined },
        { preserveState: true, replace: true }
    );
});

function confirmDelete(id) {
    confirmingDeleteId.value = id;
}

function destroy() {
    router.delete(route('accounting.accounts.destroy', confirmingDeleteId.value), {
        onFinish: () => (confirmingDeleteId.value = null),
    });
}
</script>

<template>
    <Head title="Chart of Accounts" />

    <AppLayout :breadcrumbs="[{ label: 'Accounting' }, { label: 'Chart of Accounts' }]">
        <template #header>
            <PageHeader title="Chart of Accounts">
                <template #actions>
                    <Link :href="route('accounting.accounts.create')">
                        <PrimaryButton>New Account</PrimaryButton>
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
            v-if="$page.props.errors?.account"
            class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            {{ $page.props.errors.account }}
        </div>

        <div class="mb-4 flex flex-col gap-3 sm:flex-row">
            <input
                v-model="search"
                type="text"
                placeholder="Search by code or name..."
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs"
            />
            <select
                v-model="type"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs"
            >
                <option value="">All types</option>
                <option v-for="t in types" :key="t" :value="t">
                    {{ t.charAt(0).toUpperCase() + t.slice(1) }}
                </option>
            </select>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Parent</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Opening Balance</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="account in accounts.data" :key="account.id">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ account.code }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ account.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm capitalize text-gray-700">{{ account.type }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                            {{ account.parent ? `${account.parent.code} — ${account.parent.name}` : '—' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ account.opening_balance }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium"
                                :class="account.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                            >
                                {{ account.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <Link
                                :href="route('accounting.accounts.edit', account.id)"
                                class="mr-3 text-indigo-600 hover:text-indigo-900"
                            >
                                Edit
                            </Link>
                            <button class="text-red-600 hover:text-red-900" @click="confirmDelete(account.id)">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <tr v-if="accounts.data.length === 0">
                        <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                            No accounts found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="accounts.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, index) in accounts.links"
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

        <Modal :show="confirmingDeleteId !== null" @close="confirmingDeleteId = null">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Delete this account?</h2>
                <p class="mt-1 text-sm text-gray-500">
                    This action cannot be undone. Accounts with child accounts cannot be deleted.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingDeleteId = null">Cancel</SecondaryButton>
                    <DangerButton @click="destroy">Delete</DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
