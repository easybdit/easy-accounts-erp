<script setup>
import { usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const page = usePage();

defineEmits(['toggle-sidebar']);
</script>

<template>
    <header class="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4">
        <button
            type="button"
            class="rounded-md p-2 text-gray-500 hover:bg-gray-100 md:hidden"
            @click="$emit('toggle-sidebar')"
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <div class="flex-1" />

        <Dropdown align="right" width="48">
            <template #trigger>
                <button
                    type="button"
                    class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-600 transition hover:text-gray-800 focus:outline-none"
                >
                    {{ page.props.auth.user.name }}
                    <svg class="ms-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            fill-rule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
            </template>
            <template #content>
                <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
            </template>
        </Dropdown>
    </header>
</template>
