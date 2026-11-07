<script setup lang="ts">
import { computed, ref } from 'vue';
import MerchantAvatar from './MerchantAvatar.vue';

interface MerchantOption {
    value: string | number;
    label: string;
    initials?: string | null;
    logo_url?: string | null;
    description?: string | null;
    disabled?: boolean;
}

interface Props {
    id?: string;
    modelValue: string | number | null;
    options: MerchantOption[];
    placeholder?: string;
    disabled?: boolean;
    emptyLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    options: () => [],
    placeholder: '',
    disabled: false,
    emptyLabel: '',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
}>();

const isOpen = ref(false);

const selected = computed(() =>
    props.options.find((opt) => String(opt.value) === String(props.modelValue))
);

function toggle() {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
}

function close() {
    isOpen.value = false;
}

function selectOption(option: MerchantOption) {
    if (option.disabled) return;
    emit('update:modelValue', option.value);
    close();
}

function handleBlur(event: FocusEvent) {
    const current = event.currentTarget as HTMLElement | null;
    const related = event.relatedTarget as HTMLElement | null;
    if (!current) return;
    if (!related || !current.contains(related)) {
        close();
    }
}
</script>

<template>
    <div class="dropdown w-full" :class="{ 'dropdown-open': isOpen }" @focusout="handleBlur">
        <button
            type="button"
            class="btn btn-outline border border-base-300 w-full justify-between"
            :id="id"
            :class="{ 'opacity-60': disabled }"
            :disabled="disabled"
            @click="toggle"
            @keydown.enter.prevent="toggle"
            @keydown.space.prevent="toggle"
        >
            <div class="flex items-center gap-3 min-w-0">
                <MerchantAvatar
                    v-if="selected"
                    :name="selected.label"
                    :initials="selected.initials"
                    :logo-url="selected.logo_url"
                    size="sm"
                />
                <div class="flex-1 text-left truncate">
                    <span v-if="selected">{{ selected.label }}</span>
                    <span v-else class="opacity-60">{{ placeholder }}</span>
                </div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
            </svg>
        </button>

        <ul class="dropdown-content menu bg-base-100 rounded-box shadow w-full mt-2 max-h-72 overflow-auto">
            <li v-if="!options.length" class="px-4 py-3 text-sm opacity-70">
                {{ emptyLabel || placeholder }}
            </li>
            <li v-for="option in options" :key="option.value">
                <button
                    type="button"
                    class="flex items-center gap-3 px-3 py-2 w-full text-left"
                    :class="{ 'opacity-60': option.disabled }"
                    :disabled="option.disabled"
                    @click="selectOption(option)"
                >
                    <MerchantAvatar
                        :name="option.label"
                        :initials="option.initials"
                        :logo-url="option.logo_url"
                        size="sm"
                    />
                    <div class="flex-1 min-w-0">
                        <div class="font-medium truncate">{{ option.label }}</div>
                    </div>
                    <div v-if="selected && String(selected.value) === String(option.value)" class="text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                        </svg>
                    </div>
                </button>
            </li>
        </ul>
    </div>
</template>


