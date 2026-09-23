<script setup>
import { computed } from 'vue';

const props = defineProps({
    permissions: {
        type: Array,
        required: true,
    },
    modelValue: {
        type: Array,
        required: true,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const groupedPermissions = computed(() => {
    const groups = {};
    for (const name of props.permissions) {
        const [module] = name.split('.');
        groups[module] ??= [];
        groups[module].push(name);
    }
    return groups;
});

function isChecked(name) {
    return props.modelValue.includes(name);
}

function toggle(name) {
    emit('update:modelValue', isChecked(name) ? props.modelValue.filter((n) => n !== name) : [...props.modelValue, name]);
}

// 'all' | 'some' | 'none' — drives the group header checkbox's checked/indeterminate state.
function groupState(names) {
    const selected = names.filter((name) => isChecked(name)).length;
    if (selected === 0) return 'none';
    return selected === names.length ? 'all' : 'some';
}

function toggleGroup(names) {
    if (props.disabled) return;
    const next = groupState(names) === 'all'
        ? props.modelValue.filter((name) => !names.includes(name))
        : [...new Set([...props.modelValue, ...names])];
    emit('update:modelValue', next);
}
</script>

<template>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div v-for="(names, module) in groupedPermissions" :key="module" class="rounded-md border border-gray-200 p-3">
            <label
                class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-gray-500"
                :class="disabled ? '' : 'cursor-pointer select-none hover:text-gray-700'"
                :title="disabled ? undefined : `Select all ${module} permissions`"
            >
                <input
                    type="checkbox"
                    :checked="groupState(names) === 'all'"
                    :indeterminate="groupState(names) === 'some'"
                    :disabled="disabled"
                    class="rounded border-gray-300"
                    @change="toggleGroup(names)"
                />
                {{ module }}
            </label>
            <div class="space-y-1">
                <label v-for="name in names" :key="name" class="flex items-center gap-2 text-sm text-gray-700">
                    <input
                        type="checkbox"
                        :checked="isChecked(name)"
                        :disabled="disabled"
                        class="rounded border-gray-300"
                        @change="toggle(name)"
                    />
                    {{ name }}
                </label>
            </div>
        </div>
    </div>
</template>
