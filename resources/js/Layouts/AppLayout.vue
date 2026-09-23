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
// Desktop icon-collapse mode (AdminLTE-style). AppLayout remounts on every
// Inertia navigation, so this preference is kept in localStorage rather than
// component state, otherwise it would reset on every page change.
const sidebarCollapsed = ref(localStorage.getItem('sidebar-collapsed') === '1');
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const page = usePage();
const toast = useToast();

function toggleSidebar() {
    // < md: the sidebar is an off-canvas overlay, so the navbar button
    // shows/hides it. >= md: it's always visible, so the same button
    // instead collapses it down to an icon-only rail.
    if (window.matchMedia('(min-width: 768px)').matches) {
        sidebarCollapsed.value = !sidebarCollapsed.value;
        localStorage.setItem('sidebar-collapsed', sidebarCollapsed.value ? '1' : '0');
    } else {
        sidebarOpen.value = !sidebarOpen.value;
    }
}

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

        <Sidebar :open="sidebarOpen" :collapsed="sidebarCollapsed" @close="sidebarOpen = false" />

        <div class="flex min-h-screen flex-1 flex-col md:pl-0">
            <Navbar @toggle-sidebar="toggleSidebar" />

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
