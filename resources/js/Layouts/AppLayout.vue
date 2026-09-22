<script setup>
import { onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Toast from '@/Components/Toast.vue';
import { useToast } from '@/Composables/useToast';

defineProps({
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
});

const sidebarOpen = ref(false);
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const page = usePage();
const toast = useToast();

onMounted(() => {
    if (page.props.flash?.success) {
        toast.success(page.props.flash.success);
    }
    if (page.props.flash?.error) {
        toast.error(page.props.flash.error);
    }
});
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <Toast />

        <Sidebar :open="sidebarOpen" @close="sidebarOpen = false" />

        <div class="flex min-h-screen flex-1 flex-col md:pl-0">
            <Navbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <Breadcrumb v-if="breadcrumbs.length" :items="breadcrumbs" class="mb-4" />

                <div v-if="$slots.header" class="mb-6">
                    <slot name="header" />
                </div>

                <slot />
            </main>

            <footer class="border-t border-gray-200 bg-white px-4 py-3 text-center text-xs text-gray-400 sm:px-6 lg:px-8">
                {{ appName }} &copy; {{ new Date().getFullYear() }}
            </footer>
        </div>
    </div>
</template>
