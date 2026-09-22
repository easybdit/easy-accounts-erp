<script setup>
import { onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';

const groups = ref([]);
const count = ref(0);
const loaded = ref(false);

onMounted(async () => {
    try {
        const response = await window.axios.get(route('notifications.index'));
        groups.value = response.data.groups;
        count.value = response.data.count;
    } catch {
        // Silently ignore — the bell just stays empty rather than breaking the page.
    } finally {
        loaded.value = true;
    }
});
</script>

<template>
    <Dropdown align="right" width="96">
        <template #trigger>
            <button
                type="button"
                class="relative rounded-md p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700"
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                    />
                </svg>
                <span
                    v-if="count > 0"
                    class="absolute right-1 top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white"
                >
                    {{ count > 9 ? '9+' : count }}
                </span>
            </button>
        </template>

        <template #content>
            <div class="max-h-96 overflow-y-auto">
                <p class="border-b border-gray-100 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-400">
                    Notifications
                </p>

                <div v-if="loaded && groups.length === 0" class="px-4 py-6 text-center text-sm text-gray-400">
                    You're all caught up.
                </div>

                <div v-for="group in groups" :key="group.label">
                    <p class="bg-gray-50 px-4 py-1 text-xs font-medium text-gray-500">{{ group.label }}</p>
                    <Link
                        v-for="(item, index) in group.items"
                        :key="index"
                        :href="item.link"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >
                        {{ item.message }}
                    </Link>
                </div>
            </div>
        </template>
    </Dropdown>
</template>
