import { reactive } from 'vue';

const toasts = reactive([]);
let nextId = 1;

function dismiss(id) {
    const index = toasts.findIndex((toast) => toast.id === id);
    if (index !== -1) {
        toasts.splice(index, 1);
    }
}

function push(type, message, { timeout = 5000 } = {}) {
    if (!message) {
        return;
    }

    const id = nextId++;
    toasts.push({ id, type, message });

    if (timeout > 0) {
        setTimeout(() => dismiss(id), timeout);
    }

    return id;
}

export function useToast() {
    return {
        toasts,
        success: (message, options) => push('success', message, options),
        error: (message, options) => push('error', message, options),
        dismiss,
    };
}
