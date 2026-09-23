<script setup>
import { ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Card from '@/Components/Card.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    settings: Object,
    ipWhitelistEntries: Array,
    currentIp: String,
});

const page = usePage();

function canManage() {
    return page.props.auth.permissions?.includes('settings.manage') ?? false;
}

const tabs = [
    { key: 'logo', label: 'Company Logo' },
    { key: 'email', label: 'Email' },
    { key: 'payment', label: 'Payment Gateway' },
    { key: 'security', label: 'Login Security' },
    { key: 'ip-whitelist', label: 'IP Whitelist' },
    { key: 'period-lock', label: 'Period Lock' },
    { key: 'numbering', label: 'Document Numbering' },
];
const activeTab = ref('logo');

// --- Company logo -------------------------------------------------------

const logoForm = useForm({ logo: null });
const logoInput = ref(null);
const logoPreview = ref(null);

function pickLogo() {
    logoInput.value?.click();
}

function onLogoChosen(event) {
    const file = event.target.files[0];
    if (!file) return;

    logoForm.logo = file;
    logoPreview.value = URL.createObjectURL(file);
}

function submitLogo() {
    logoForm.post(route('accounting.settings.logo.update'), {
        forceFormData: true,
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            logoForm.reset();
            logoPreview.value = null;
            if (logoInput.value) logoInput.value.value = '';
        },
    });
}

function removeLogo() {
    logoForm.delete(route('accounting.settings.logo.destroy'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            logoPreview.value = null;
        },
    });
}

// --- Email (SMTP) --------------------------------------------------------

const mailForm = useForm({
    mail_mailer: props.settings.mail_mailer ?? 'smtp',
    mail_host: props.settings.mail_host ?? '',
    mail_port: props.settings.mail_port ?? 587,
    mail_username: props.settings.mail_username ?? '',
    mail_password: '',
    mail_encryption: props.settings.mail_encryption ?? 'tls',
    mail_from_address: props.settings.mail_from_address ?? '',
    mail_from_name: props.settings.mail_from_name ?? '',
});

function submitMail() {
    mailForm.put(route('accounting.settings.mail.update'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            mailForm.mail_password = '';
        },
    });
}

const testMailForm = useForm({ test_email: '' });

function sendTestMail() {
    testMailForm.post(route('accounting.settings.mail.test'), { preserveState: true, preserveScroll: true });
}

// --- Payment gateway (SSLCommerz) ----------------------------------------

const gatewayForm = useForm({
    sslcommerz_enabled: props.settings.sslcommerz_enabled ?? false,
    sslcommerz_store_id: props.settings.sslcommerz_store_id ?? '',
    sslcommerz_store_password: '',
    sslcommerz_sandbox: props.settings.sslcommerz_sandbox ?? true,
    sslcommerz_currency: props.settings.sslcommerz_currency ?? 'BDT',
});

function submitGateway() {
    gatewayForm.put(route('accounting.settings.payment-gateway.update'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            gatewayForm.sslcommerz_store_password = '';
        },
    });
}

// --- Login security (brute-force lock + captcha) -------------------------

const securityForm = useForm({
    login_max_attempts: props.settings.login_max_attempts ?? 5,
    login_lockout_minutes: props.settings.login_lockout_minutes ?? 15,
    login_captcha_enabled: props.settings.login_captcha_enabled ?? true,
});

function submitSecurity() {
    securityForm.put(route('accounting.settings.login-security.update'), {
        preserveState: true,
        preserveScroll: true,
    });
}

// --- IP whitelist ---------------------------------------------------------

const ipToggleForm = useForm({ ip_whitelist_enabled: props.settings.ip_whitelist_enabled ?? false });

function submitIpToggle() {
    ipToggleForm.put(route('accounting.settings.ip-whitelist-enabled.update'), {
        preserveState: true,
        preserveScroll: true,
    });
}

const ipEntryForm = useForm({ ip_address: '', label: '' });

function addIpEntry() {
    ipEntryForm.post(route('security.ip-whitelist.store'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => ipEntryForm.reset(),
    });
}

function addCurrentIp() {
    ipEntryForm.ip_address = props.currentIp;
    addIpEntry();
}

function removeIpEntry(entry) {
    router.delete(route('security.ip-whitelist.destroy', entry.id), { preserveState: true, preserveScroll: true });
}

// --- Period lock -----------------------------------------------------

const lockForm = useForm({
    locked_through_date: props.settings.locked_through_date ?? '',
});

function submitLock() {
    lockForm.put(route('accounting.settings.update'), { preserveState: true, preserveScroll: true });
}

function removeLock() {
    lockForm.locked_through_date = '';
    lockForm.put(route('accounting.settings.update'), { preserveState: true, preserveScroll: true });
}

// --- Document numbering --------------------------------------------------

// Defaults mirror GenerateDocumentNumber::DEFAULTS — shown as placeholders
// so an empty field visibly means "use this," not "blank."
const documentTypes = [
    { key: 'invoice', label: 'Invoice', default: 'INV' },
    { key: 'estimate', label: 'Estimate', default: 'EST' },
    { key: 'credit_note', label: 'Credit Note', default: 'CN' },
    { key: 'payment', label: 'Customer Payment', default: 'PAY' },
    { key: 'sales_receipt', label: 'Sales Receipt', default: 'SR' },
    { key: 'bill', label: 'Bill', default: 'BILL' },
    { key: 'purchase_order', label: 'Purchase Order', default: 'PO' },
    { key: 'vendor_credit', label: 'Vendor Credit', default: 'VC' },
    { key: 'vendor_payment', label: 'Vendor Payment', default: 'VPAY' },
    { key: 'expense', label: 'Expense', default: 'EXP' },
    { key: 'bank_deposit', label: 'Bank Deposit', default: 'DEP' },
    { key: 'transfer', label: 'Transfer', default: 'TRF' },
];

const numberingForm = useForm(
    Object.fromEntries(documentTypes.map((type) => [type.key, props.settings.document_number_prefixes?.[type.key] ?? '']))
);

function submitNumbering() {
    numberingForm.put(route('accounting.settings.document-numbering.update'), { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <Head title="Company Settings" />

    <AppLayout :breadcrumbs="[{ label: 'Accounting' }, { label: 'Company Settings' }]">
        <template #header>
            <PageHeader title="Company Settings" />
        </template>

        <div class="max-w-2xl">
            <div class="mb-4 flex flex-wrap gap-1 border-b border-gray-200">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="border-b-2 px-3 py-2 text-sm font-medium transition"
                    :class="activeTab === tab.key
                        ? 'border-indigo-600 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'"
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- Company Logo -->
            <Card v-show="activeTab === 'logo'" padded>
                <h2 class="text-sm font-semibold text-gray-700">Company Logo</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Shown in the sidebar and on the sign-in page. PNG, JPG, SVG or WebP, up to 2MB.
                </p>

                <div class="mt-4 flex items-center gap-4">
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 p-2">
                        <img
                            v-if="logoPreview || page.props.company?.logoUrl"
                            :src="logoPreview || page.props.company.logoUrl"
                            alt="Company logo"
                            class="max-h-full max-w-full object-contain"
                        />
                        <span v-else class="text-xs text-gray-400">No logo</span>
                    </div>

                    <div v-if="canManage()" class="space-y-2">
                        <input ref="logoInput" type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden" @change="onLogoChosen" />
                        <div class="flex flex-wrap gap-2">
                            <SecondaryButton type="button" @click="pickLogo">Choose Image</SecondaryButton>
                            <PrimaryButton v-if="logoForm.logo" :loading="logoForm.processing" @click="submitLogo">
                                Save Logo
                            </PrimaryButton>
                            <SecondaryButton v-if="page.props.company?.logoUrl" type="button" @click="removeLogo">
                                Remove Logo
                            </SecondaryButton>
                        </div>
                        <InputError :message="logoForm.errors.logo" />
                    </div>
                </div>
            </Card>

            <!-- Email (SMTP) -->
            <Card v-show="activeTab === 'email'" padded>
                <h2 class="text-sm font-semibold text-gray-700">Email (SMTP)</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Controls how the app sends email — overdue invoice reminders, low-stock alerts, password resets.
                    Leave everything blank to keep using the server's default mail configuration.
                </p>

                <form class="mt-4 space-y-4" @submit.prevent="submitMail">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="mail_host" value="SMTP Host" />
                            <TextInput id="mail_host" v-model="mailForm.mail_host" type="text" class="mt-1 block w-full" placeholder="smtp.example.com" :disabled="!canManage()" />
                            <InputError :message="mailForm.errors.mail_host" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="mail_port" value="Port" />
                            <TextInput id="mail_port" v-model="mailForm.mail_port" type="number" class="mt-1 block w-full" :disabled="!canManage()" />
                            <InputError :message="mailForm.errors.mail_port" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="mail_username" value="Username" />
                            <TextInput id="mail_username" v-model="mailForm.mail_username" type="text" class="mt-1 block w-full" autocomplete="off" :disabled="!canManage()" />
                            <InputError :message="mailForm.errors.mail_username" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="mail_password" value="Password" />
                            <TextInput
                                id="mail_password"
                                v-model="mailForm.mail_password"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                :placeholder="settings.mail_password_set ? 'Leave blank to keep current' : ''"
                                :disabled="!canManage()"
                            />
                            <InputError :message="mailForm.errors.mail_password" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="mail_encryption" value="Encryption" />
                            <select id="mail_encryption" v-model="mailForm.mail_encryption" class="mt-1 block w-full rounded-md border-gray-300 text-sm" :disabled="!canManage()">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="">None</option>
                            </select>
                            <InputError :message="mailForm.errors.mail_encryption" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="mail_from_address" value="From Address" />
                            <TextInput id="mail_from_address" v-model="mailForm.mail_from_address" type="email" class="mt-1 block w-full" :disabled="!canManage()" />
                            <InputError :message="mailForm.errors.mail_from_address" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="mail_from_name" value="From Name" />
                            <TextInput id="mail_from_name" v-model="mailForm.mail_from_name" type="text" class="mt-1 block w-full" :disabled="!canManage()" />
                            <InputError :message="mailForm.errors.mail_from_name" class="mt-2" />
                        </div>
                    </div>

                    <PrimaryButton v-if="canManage()" :loading="mailForm.processing">Save Email Settings</PrimaryButton>
                </form>

                <div v-if="canManage()" class="mt-6 border-t border-gray-100 pt-4">
                    <InputLabel value="Send a Test Email" />
                    <p class="mt-1 text-xs text-gray-400">Saves first if you have unsaved changes above.</p>
                    <form class="mt-2 flex flex-wrap items-start gap-2" @submit.prevent="sendTestMail">
                        <TextInput v-model="testMailForm.test_email" type="email" placeholder="you@example.com" class="block w-64" required />
                        <SecondaryButton type="submit" :loading="testMailForm.processing">Send Test</SecondaryButton>
                    </form>
                    <InputError :message="testMailForm.errors.test_email" class="mt-2" />
                </div>
            </Card>

            <!-- Payment gateway (SSLCommerz) -->
            <Card v-show="activeTab === 'payment'" padded>
                <h2 class="text-sm font-semibold text-gray-700">Payment Gateway — SSLCommerz</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Controls the "Pay Online" links customers can use to settle an invoice through SSLCommerz.
                </p>

                <form class="mt-4 space-y-4" @submit.prevent="submitGateway">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input v-model="gatewayForm.sslcommerz_enabled" type="checkbox" class="rounded border-gray-300" :disabled="!canManage()" />
                        Enable online payments
                    </label>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="sslcommerz_store_id" value="Store ID" />
                            <TextInput id="sslcommerz_store_id" v-model="gatewayForm.sslcommerz_store_id" type="text" class="mt-1 block w-full" autocomplete="off" :disabled="!canManage()" />
                            <InputError :message="gatewayForm.errors.sslcommerz_store_id" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="sslcommerz_store_password" value="Store Password" />
                            <TextInput
                                id="sslcommerz_store_password"
                                v-model="gatewayForm.sslcommerz_store_password"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                :placeholder="settings.sslcommerz_store_password_set ? 'Leave blank to keep current' : ''"
                                :disabled="!canManage()"
                            />
                            <InputError :message="gatewayForm.errors.sslcommerz_store_password" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="sslcommerz_currency" value="Currency" />
                            <TextInput id="sslcommerz_currency" v-model="gatewayForm.sslcommerz_currency" type="text" class="mt-1 block w-full" :disabled="!canManage()" />
                            <InputError :message="gatewayForm.errors.sslcommerz_currency" class="mt-2" />
                        </div>
                        <div class="flex items-end pb-2">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input v-model="gatewayForm.sslcommerz_sandbox" type="checkbox" class="rounded border-gray-300" :disabled="!canManage()" />
                                Sandbox / test mode
                            </label>
                        </div>
                    </div>

                    <p v-if="gatewayForm.sslcommerz_enabled && !gatewayForm.sslcommerz_sandbox" class="rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-700">
                        Live mode — real customer payments will be processed once saved.
                    </p>

                    <PrimaryButton v-if="canManage()" :loading="gatewayForm.processing">Save Payment Gateway Settings</PrimaryButton>
                </form>
            </Card>

            <!-- Login security -->
            <Card v-show="activeTab === 'security'" padded>
                <h2 class="text-sm font-semibold text-gray-700">Login Security</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Brute-force protection: an account is locked out for a period after too many wrong passwords in a
                    row. A locked account can also be unlocked immediately from Security → Users.
                </p>

                <form class="mt-4 space-y-4" @submit.prevent="submitSecurity">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="login_max_attempts" value="Failed attempts before lockout" />
                            <TextInput id="login_max_attempts" v-model="securityForm.login_max_attempts" type="number" min="3" max="20" class="mt-1 block w-full" :disabled="!canManage()" />
                            <InputError :message="securityForm.errors.login_max_attempts" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="login_lockout_minutes" value="Lockout duration (minutes)" />
                            <TextInput id="login_lockout_minutes" v-model="securityForm.login_lockout_minutes" type="number" min="1" max="1440" class="mt-1 block w-full" :disabled="!canManage()" />
                            <InputError :message="securityForm.errors.login_lockout_minutes" class="mt-2" />
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input v-model="securityForm.login_captcha_enabled" type="checkbox" class="rounded border-gray-300" :disabled="!canManage()" />
                        Require a math security check on the login form
                    </label>

                    <PrimaryButton v-if="canManage()" :loading="securityForm.processing">Save Login Security Settings</PrimaryButton>
                </form>
            </Card>

            <!-- IP Whitelist -->
            <Card v-show="activeTab === 'ip-whitelist'" padded>
                <h2 class="text-sm font-semibold text-gray-700">IP Whitelist</h2>
                <p class="mt-1 text-sm text-gray-500">
                    When enabled, only the IP addresses listed below may sign in. If the list is empty, the
                    restriction is skipped entirely — this can never lock everyone out by accident.
                </p>

                <div class="mt-3 rounded-md bg-gray-50 px-3 py-2 text-xs text-gray-500">
                    Your current IP address is <span class="font-mono font-medium text-gray-700">{{ currentIp }}</span>.
                </div>

                <form v-if="canManage()" class="mt-4" @submit.prevent="submitIpToggle">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input v-model="ipToggleForm.ip_whitelist_enabled" type="checkbox" class="rounded border-gray-300" />
                        Restrict sign-in to whitelisted IP addresses only
                    </label>
                    <p v-if="ipToggleForm.ip_whitelist_enabled && ipWhitelistEntries.length === 0" class="mt-2 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-700">
                        No IP addresses are listed yet — add at least one below before this can take effect.
                    </p>
                    <InputError :message="ipToggleForm.errors.ip_whitelist_enabled" class="mt-2" />
                    <PrimaryButton class="mt-3" :loading="ipToggleForm.processing">Save</PrimaryButton>
                </form>

                <div v-if="canManage()" class="mt-6 border-t border-gray-100 pt-4">
                    <InputLabel value="Add an IP Address" />
                    <form class="mt-2 flex flex-wrap items-start gap-2" @submit.prevent="addIpEntry">
                        <TextInput v-model="ipEntryForm.ip_address" type="text" placeholder="203.0.113.7 or 203.0.113.0/24" class="block w-56" />
                        <TextInput v-model="ipEntryForm.label" type="text" placeholder="Label (optional)" class="block w-40" />
                        <SecondaryButton type="submit" :loading="ipEntryForm.processing">Add</SecondaryButton>
                        <SecondaryButton type="button" @click="addCurrentIp">Add My Current IP</SecondaryButton>
                    </form>
                    <InputError :message="ipEntryForm.errors.ip_address" class="mt-2" />
                </div>

                <div class="mt-6 overflow-x-auto border-t border-gray-100 pt-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">IP / Range</th>
                                <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Label</th>
                                <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Added by</th>
                                <th class="px-2 py-2" />
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="entry in ipWhitelistEntries" :key="entry.id">
                                <td class="whitespace-nowrap px-2 py-2 font-mono text-sm text-gray-700">{{ entry.ip_address }}</td>
                                <td class="whitespace-nowrap px-2 py-2 text-sm text-gray-500">{{ entry.label || '—' }}</td>
                                <td class="whitespace-nowrap px-2 py-2 text-sm text-gray-500">{{ entry.created_by || '—' }}</td>
                                <td class="whitespace-nowrap px-2 py-2 text-right">
                                    <button v-if="canManage()" type="button" class="text-sm text-red-600 hover:text-red-800" @click="removeIpEntry(entry)">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="ipWhitelistEntries.length === 0" class="py-4 text-center text-sm text-gray-400">No IP addresses added yet.</p>
                </div>
            </Card>

            <!-- Period Lock -->
            <Card v-show="activeTab === 'period-lock'" padded>
                <h2 class="text-sm font-semibold text-gray-700">Period Lock</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Once a period has been reported on and reconciled, lock it to prevent any new journal, invoice,
                    bill, payment, or other posting dated on or before the lock date — including generated documents
                    like depreciation and revenue recognition. Draft documents can still be created and edited; only
                    posting into a locked date is blocked.
                </p>

                <div v-if="settings.locked_through_date" class="mt-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Currently locked through <strong>{{ settings.locked_through_date }}</strong>.
                </div>
                <div v-else class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">
                    No lock is set — all dates are open for posting.
                </div>

                <form class="mt-6" @submit.prevent="submitLock">
                    <InputLabel for="locked_through_date" value="Locked Through Date" />
                    <TextInput
                        id="locked_through_date"
                        v-model="lockForm.locked_through_date"
                        type="date"
                        class="mt-1 block w-full max-w-xs"
                        :disabled="!canManage()"
                    />
                    <p class="mt-1 text-xs text-gray-400">Leave blank and save to remove the lock entirely.</p>
                    <InputError :message="lockForm.errors.locked_through_date" class="mt-2" />

                    <div v-if="canManage()" class="mt-6 flex gap-3">
                        <PrimaryButton :loading="lockForm.processing">Save</PrimaryButton>
                        <SecondaryButton v-if="settings.locked_through_date" type="button" @click="removeLock">
                            Remove Lock
                        </SecondaryButton>
                    </div>
                </form>
            </Card>

            <!-- Document Numbering -->
            <Card v-show="activeTab === 'numbering'" padded>
                <h2 class="text-sm font-semibold text-gray-700">Document Numbering</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Each number is generated as <span class="font-mono">{PREFIX}-{year}-{sequence}</span>, e.g.
                    <span class="font-mono">INV-2026-0001</span>. Set a prefix per document type here, or leave a
                    field blank to use its default shown as a placeholder.
                </p>

                <form class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3" @submit.prevent="submitNumbering">
                    <div v-for="type in documentTypes" :key="type.key">
                        <InputLabel :for="`prefix_${type.key}`" :value="type.label" />
                        <TextInput
                            :id="`prefix_${type.key}`"
                            v-model="numberingForm[type.key]"
                            type="text"
                            class="mt-1 block w-full"
                            :placeholder="type.default"
                            :disabled="!canManage()"
                        />
                        <InputError :message="numberingForm.errors[type.key]" class="mt-2" />
                    </div>

                    <div v-if="canManage()" class="sm:col-span-3">
                        <PrimaryButton :loading="numberingForm.processing">Save Numbering Settings</PrimaryButton>
                    </div>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
