<script setup lang="ts">
import { ref, watch } from 'vue';
import { vueLang } from '@erag/lang-sync-inertia';

interface Props {
    type?: 'error' | 'success' | 'info' | 'warning';
    message?: string;
    closable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'info',
    closable: true,
});

const { __ } = vueLang();

const alertClass = {
    error: 'alert-error',
    success: 'alert-success',
    info: 'alert-info',
    warning: 'alert-warning',
};

const isVisible = ref(Boolean(props.message));

watch(
    () => props.message,
    (value) => {
        isVisible.value = Boolean(value);
    },
    { immediate: true },
);

function closeAlert() {
    isVisible.value = false;
}
</script>

<template>
    <div
        v-if="isVisible && message"
        role="alert"
        :class="['alert', 'flex items-start', 'gap-4', alertClass[props.type]]"
    >
        <span class="flex-1">{{ message }}</span>
        <button
            v-if="props.closable"
            type="button"
            class="btn btn-ghost btn-xs btn-circle"
            :aria-label="__('frontend.common.close')"
            @click="closeAlert"
        >
            ✕
        </button>
    </div>
</template>





