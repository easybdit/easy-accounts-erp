<script setup>
import { useToast } from '@/Composables/useToast';

const { toasts, dismiss } = useToast();

const styles = {
    success: {
        wrapper: 'border-green-200 bg-green-50',
        icon: 'text-green-500',
        text: 'text-green-800',
        path: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    error: {
        wrapper: 'border-red-200 bg-red-50',
        icon: 'text-red-500',
        text: 'text-red-800',
        path: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
    },
};
</script>

<template>
    <Teleport to="body">
        <div class="pointer-events-none fixed inset-x-0 top-4 z-50 flex flex-col items-end gap-2 px-4 sm:items-end sm:px-6">
            <TransitionGroup
                enter-active-class="transform transition duration-200 ease-out"
                enter-from-class="translate-y-2 opacity-0 sm:translate-x-2 sm:translate-y-0"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transform transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border p-4 shadow-lg"
                    :class="styles[toast.type].wrapper"
                    role="alert"
                >
                    <svg class="mt-0.5 h-5 w-5 shrink-0" :class="styles[toast.type].icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="styles[toast.type].path" />
                    </svg>
                    <p class="flex-1 text-sm font-medium" :class="styles[toast.type].text">{{ toast.message }}</p>
                    <button
                        type="button"
                        class="shrink-0 rounded-md p-0.5 text-gray-400 transition hover:text-gray-600"
                        @click="dismiss(toast.id)"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
